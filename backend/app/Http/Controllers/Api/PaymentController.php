<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PaymentRecordedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Services\DataScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Helper to verify if user has authority to confirm/manage payments.
     * Designed to support Accountant, Finance, Admin, Super Admin or custom permission.
     */
    private function authorizeAdmin(Request $request): bool
    {
        $user = $request->user();
        if (! $user) return false;

        return $user->hasAnyRole(['Admin', 'Super Admin', 'Finance', 'Accountant', 'Manager'])
            || $user->can('payments.manage')
            || $user->can('payments.confirm');
    }

    // ─── REP-FACING ENDPOINTS ──────────────────────────────────────────────────

    /**
     * POST /api/payments/collect
     * Field rep submits a payment collection record.
     */
    public function collect(Request $request): JsonResponse
    {
        $request->validate([
            'party_id'             => 'required|exists:customers,id',
            'amount'               => 'required|numeric|min:1',
            'payment_mode'         => 'required|in:cash,upi,bank_transfer,cheque',
            'reference_number'     => 'nullable|required_if:payment_mode,upi,bank_transfer,cheque|string|max:100',
            'proof_photo'          => 'nullable|required_if:payment_mode,cheque|image|max:10240',
            'visit_id'             => 'nullable|exists:visits,id',
            'collection_latitude'  => 'nullable|numeric',
            'collection_longitude' => 'nullable|numeric',
            'allocated_invoice_id' => 'nullable|integer',
            'notes'                => 'nullable|string|max:1000',
            'idempotency_key'      => 'nullable|string|max:64',
        ]);

        $user = $request->user();

        // 1. Anti-Duplicate / Idempotency check:
        // If same rep submitted for same party + amount within the last 60 seconds
        $recentDuplicate = Payment::where('collected_by', $user->id)
            ->where('party_id', $request->party_id)
            ->where('amount', $request->amount)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->first();

        if ($recentDuplicate) {
            return response()->json([
                'success' => true,
                'message' => 'Payment already submitted. (Duplicate submission prevented)',
                'data'    => $recentDuplicate->load('party:id,name,mobile,address'),
            ], 200);
        }

        // 2. Handle Proof Photo Upload
        $proofPhotoUrl = null;
        if ($request->hasFile('proof_photo')) {
            $file = $request->file('proof_photo');
            $fileName = 'payment_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/payments', $fileName, 'public');
            $proofPhotoUrl = '/storage/' . $path;
        }

        // 3. Generate unique Receipt No
        $receiptNo = 'RCP-' . date('Ymd') . '-' . strtoupper(Str::random(4)) . rand(10, 99);

        // 4. Create Payment Record with status = 'pending'
        $payment = Payment::create([
            'receipt_no'           => $receiptNo,
            'party_id'             => $request->party_id,
            'collected_by'         => $user->id,
            'visit_id'             => $request->visit_id,
            'amount'               => $request->amount,
            'payment_mode'         => $request->payment_mode,
            'reference_number'     => $request->reference_number,
            'proof_photo_url'      => $proofPhotoUrl,
            'collection_latitude'  => $request->collection_latitude,
            'collection_longitude' => $request->collection_longitude,
            'collection_datetime'  => now(),
            'allocated_invoice_id' => $request->allocated_invoice_id,
            'notes'                => $request->notes,
            'status'               => 'pending',
            'idempotency_key'      => $request->idempotency_key,
        ]);

        // 5. Notify all Admin / Finance / Accountant users
        try {
            $recipients = User::role(['Admin', 'Super Admin', 'Finance'])->get();
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new PaymentRecordedNotification($payment));
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to dispatch PaymentRecordedNotification: {$e->getMessage()}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully. Awaiting Admin confirmation.',
            'data'    => $payment->load('party:id,name,mobile,address'),
        ], 201);
    }

    /**
     * GET /api/payments/my-collections
     * List payments submitted by the authenticated field rep.
     */
    public function myCollections(Request $request): JsonResponse
    {
        $query = Payment::with([
            'party:id,name,mobile,address',
            'confirmedBy:id,name',
        ])
        ->where('collected_by', $request->user()->id)
        ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('party_id')) {
            $query->where('party_id', $request->party_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('collection_datetime', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('collection_datetime', '<=', $request->to_date);
        }

        if ($request->has('page') || $request->has('per_page')) {
            $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
            $paginated = $query->paginate($perPage);

            return response()->json([
                'success'      => true,
                'data'         => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'has_more'     => $paginated->hasMorePages(),
            ]);
        }

        $payments = $query->limit(100)->get();

        return response()->json([
            'success' => true,
            'data'    => $payments,
            'total'   => $payments->count(),
        ]);
    }

    /**
     * GET /api/parties/{id}/payments
     * Get all payment history + outstanding balance summary for a specific dealer.
     */
    public function partyPayments(Request $request, int $id): JsonResponse
    {
        $customer = Customer::with('transactions')->find($id);

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Dealer not found.'], 404);
        }

        // Calculate Dealer Ledger Balance:
        // opening_balance + sum(debit) - sum(credit)
        $debits = $customer->transactions->where('type', 'debit')->sum('amount');
        $credits = $customer->transactions->where('type', 'credit')->sum('amount');
        $openingBalance = (float) $customer->opening_balance;
        $currentOutstanding = $openingBalance + $debits - $credits;

        // Pending payments (unconfirmed)
        $pendingAmount = Payment::where('party_id', $id)->where('status', 'pending')->sum('amount');

        // Fetch payments history for this dealer
        $payments = Payment::with([
            'collectedBy:id,name',
            'confirmedBy:id,name',
        ])
        ->where('party_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'dealer' => [
                'id'                  => $customer->id,
                'name'                => $customer->name,
                'mobile'              => $customer->mobile,
                'address'             => $customer->address,
                'credit_limit'        => (float) $customer->credit_limit,
                'opening_balance'     => $openingBalance,
                'outstanding_balance' => round($currentOutstanding, 2),
                'pending_amount'      => round((float) $pendingAmount, 2),
            ],
            'data' => $payments,
        ]);
    }

    /**
     * GET /api/payments/settings
     * Returns company payment configuration (official UPI QR & Bank details).
     */
    public function getSettings(): JsonResponse
    {
        $upiId = SystemSetting::where('setting_key', 'company_upi_id')->value('setting_value') ?? 'amplindustries@icici';
        $companyName = SystemSetting::where('setting_key', 'company_name')->value('setting_value') ?? 'AMPL Industries Ltd';
        
        $bankName = SystemSetting::where('setting_key', 'company_bank_name')->value('setting_value');
        $accountNo = SystemSetting::where('setting_key', 'company_account_no')->value('setting_value');
        $ifscCode = SystemSetting::where('setting_key', 'company_ifsc_code')->value('setting_value');
        $branch = SystemSetting::where('setting_key', 'company_branch')->value('setting_value');

        if ($bankName || $accountNo || $ifscCode) {
            $bankDetails = [
                'bank_name'  => $bankName ?? 'ICICI Bank',
                'account_no' => $accountNo ?? '001105001234',
                'ifsc_code'  => $ifscCode ?? 'ICIC0000011',
                'branch'     => $branch ?? 'Main Branch',
            ];
        } else {
            $bankRaw = SystemSetting::where('setting_key', 'company_bank_details')->value('setting_value');
            $bankDetails = $bankRaw ? json_decode($bankRaw, true) : [
                'bank_name'  => 'ICICI Bank',
                'account_no' => '001105001234',
                'ifsc_code'  => 'ICIC0000011',
                'branch'     => 'Main Branch',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'company_upi_id'       => $upiId,
                'company_name'         => $companyName,
                'company_bank_details' => $bankDetails,
            ],
        ]);
    }

    // ─── ADMIN-FACING ENDPOINTS ────────────────────────────────────────────────

    /**
     * GET /api/admin/payments/pending
     * Queue of pending payments awaiting confirmation.
     */
    public function pendingQueue(Request $request): JsonResponse
    {
        if (! $this->authorizeAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = Payment::with([
            'party:id,name,mobile,address',
            'collectedBy:id,name,employee_id',
            'visit:id,check_in_at',
        ])
        ->where('status', 'pending');

        DataScope::apply($query, 'payments', 'collected_by');

        if ($request->has('page') || $request->has('per_page')) {
            $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
            $paginated = $query->orderBy('created_at', 'asc')->paginate($perPage);

            return response()->json([
                'success'      => true,
                'count'        => $paginated->total(),
                'data'         => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'has_more'     => $paginated->hasMorePages(),
            ]);
        }

        $payments = $query->orderBy('created_at', 'asc')->limit(100)->get();

        return response()->json([
            'success' => true,
            'count'   => $payments->count(),
            'data'    => $payments,
        ]);
    }

    /**
     * GET /api/admin/payments
     * Full searchable and filterable list of all payments.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        if (! $this->authorizeAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = Payment::with([
            'party:id,name,mobile,address',
            'collectedBy:id,name,employee_id',
            'confirmedBy:id,name',
        ])->orderBy('created_at', 'desc');

        $scopedIds = DataScope::visibleUserIds('payments');
        if ($scopedIds !== null) {
            $query->whereIn('collected_by', $scopedIds);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('party_id')) {
            $query->where('party_id', $request->party_id);
        }

        if ($request->filled('collected_by')) {
            if ($scopedIds === null || in_array($request->collected_by, $scopedIds)) {
                $query->where('collected_by', $request->collected_by);
            } else {
                $query->whereRaw('1=0');
            }
        }

        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('collection_datetime', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('collection_datetime', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('party', fn($pq) => $pq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('collectedBy', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $payments = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $payments,
        ]);
    }

    /**
     * PATCH /api/admin/payments/{id}/confirm
     * Confirm a pending payment and post credit entry into dealer's ledger.
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        if (! $this->authorizeAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $payment = Payment::with(['party', 'collectedBy'])->find($id);

        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Payment record not found.'], 404);
        }

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "This payment has already been processed (Current status: {$payment->status}).",
            ], 422);
        }

        if ($payment->collected_by === $request->user()->id && !$request->user()->hasRole(['Super Admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Conflict of Interest: You cannot confirm a payment you collected yourself.',
            ], 403);
        }

        try {
            DB::transaction(function () use ($payment, $request) {
                // 1. Mark payment confirmed
                $payment->update([
                    'status'       => 'confirmed',
                    'confirmed_by' => $request->user()->id,
                    'confirmed_at' => now(),
                ]);

                // 2. Add Credit Entry to Dealer Ledger (Transactions table)
                Transaction::create([
                    'party_id'       => $payment->party_id,
                    'user_id'        => $payment->collected_by,
                    'reference_type' => 'Payment',
                    'reference_id'   => $payment->id,
                    'type'           => 'credit',
                    'amount'         => $payment->amount,
                    'status'         => 'completed',
                    'notes'          => "Payment received ({$payment->receipt_no}) via " . strtoupper($payment->payment_mode),
                ]);

                // 3. Audit Log
                AuditLog::logEvent(
                    event: 'payment.confirmed',
                    auditable: $payment,
                    oldValues: ['status' => 'pending'],
                    newValues: [
                        'status'       => 'confirmed',
                        'confirmed_by' => $request->user()->id,
                        'amount'       => $payment->amount,
                    ],
                    reason: 'Admin confirmed payment received into company account'
                );
            });

            // 4. Notify Rep
            if ($payment->collectedBy) {
                try {
                    $payment->collectedBy->notify(new PaymentConfirmedNotification($payment));
                } catch (\Throwable $e) {
                    Log::warning("Failed to notify rep of payment confirmation: {$e->getMessage()}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment successfully confirmed and credited to dealer ledger.',
                'data'    => $payment->fresh(['party', 'confirmedBy']),
            ]);

        } catch (\Throwable $e) {
            Log::error("Payment confirmation failed: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment. ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/admin/payments/{id}/reject
     * Reject a pending payment with a required reason (does NOT touch ledger).
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        if (! $this->authorizeAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|min:3|max:1000',
        ]);

        $payment = Payment::with(['party', 'collectedBy'])->find($id);

        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Payment record not found.'], 404);
        }

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "This payment has already been processed (Current status: {$payment->status}).",
            ], 422);
        }

        try {
            DB::transaction(function () use ($payment, $request) {
                $payment->update([
                    'status'           => 'rejected',
                    'confirmed_by'     => $request->user()->id,
                    'confirmed_at'     => now(),
                    'rejection_reason' => $request->rejection_reason,
                ]);

                // Audit Log
                AuditLog::logEvent(
                    event: 'payment.rejected',
                    auditable: $payment,
                    oldValues: ['status' => 'pending'],
                    newValues: [
                        'status'           => 'rejected',
                        'confirmed_by'     => $request->user()->id,
                        'rejection_reason' => $request->rejection_reason,
                    ],
                    reason: $request->rejection_reason
                );
            });

            // Notify Rep
            if ($payment->collectedBy) {
                try {
                    $payment->collectedBy->notify(new PaymentRejectedNotification($payment));
                } catch (\Throwable $e) {
                    Log::warning("Failed to notify rep of payment rejection: {$e->getMessage()}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment rejected. Field rep notified.',
                'data'    => $payment->fresh(['party', 'confirmedBy']),
            ]);

        } catch (\Throwable $e) {
            Log::error("Payment rejection failed: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject payment. ' . $e->getMessage(),
            ], 500);
        }
    }
}
