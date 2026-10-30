<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebController extends Controller
{
    public function index(Request $request)
    {
        $scopedUserIds = DataScope::visibleUserIds('payments');

        $status = $request->input('status', 'all');
        $partyId = $request->input('party_id');
        $collectedBy = $request->input('collected_by');
        $search = $request->input('search');
        $activeTab = $request->input('tab', 'pending'); // 'pending' | 'all'

        // 1. Pending Queue (scoped)
        $pendingPaymentsQuery = Payment::with(['party', 'collectedBy.roles', 'visit'])
            ->where('status', 'pending');

        if ($scopedUserIds !== null) {
            $pendingPaymentsQuery->whereIn('collected_by', $scopedUserIds);
        }

        $pendingPayments = $pendingPaymentsQuery->orderBy('created_at', 'desc')->get();

        // 2. All Payments query for history tab (scoped)
        $allQuery = Payment::with(['party', 'collectedBy.roles', 'confirmedBy.roles'])
            ->orderBy('created_at', 'desc');

        if ($scopedUserIds !== null) {
            $allQuery->whereIn('collected_by', $scopedUserIds);
        }

        if ($status !== 'all') {
            $allQuery->where('status', $status);
        }

        if ($partyId) {
            $allQuery->where('party_id', $partyId);
        }

        if ($collectedBy) {
            if ($scopedUserIds === null || in_array($collectedBy, $scopedUserIds)) {
                $allQuery->where('collected_by', $collectedBy);
            } else {
                $allQuery->whereRaw('1=0');
            }
        }

        if ($search) {
            $allQuery->where(function ($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('party', fn($pq) => $pq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('collectedBy', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $allPayments = $allQuery->paginate(15)->withQueryString();

        // 3. Per-rep Pending summary widget (scoped)
        $repSummariesQuery = Payment::where('status', 'pending');
        if ($scopedUserIds !== null) {
            $repSummariesQuery->whereIn('collected_by', $scopedUserIds);
        }

        $repSummaries = $repSummariesQuery
            ->select('collected_by', DB::raw('count(*) as pending_count'), DB::raw('sum(amount) as pending_total'))
            ->groupBy('collected_by')
            ->with('collectedBy.roles')
            ->get();

        $parties = Customer::orderBy('name')->get(['id', 'name']);
        $repsQuery = User::with('roles')->where('is_active', true);
        if ($scopedUserIds !== null) {
            $repsQuery->whereIn('id', $scopedUserIds);
        } else {
            $repsQuery->whereHas('roles', fn($q) => $q->whereIn('name', ['Field Worker', 'field_worker', 'Employee']));
        }
        $reps = $repsQuery->orderBy('name')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'pendingPayments' => $pendingPayments->map(fn($p) => [
                    'id'                  => $p->id,
                    'receipt_no'          => $p->receipt_no,
                    'party_name'          => $p->party?->name ?? '—',
                    'party_mobile'        => $p->party?->mobile ?? '',
                    'collected_by_name'   => $p->collectedBy?->name_with_role ?? '—',
                    'amount'              => (float) $p->amount,
                    'amount_formatted'    => number_format((float) $p->amount, 2),
                    'payment_mode'        => $p->payment_mode,
                    'reference_number'    => $p->reference_number,
                    'proof_photo_url'     => $p->proof_photo_url ? asset($p->proof_photo_url) : null,
                    'collection_date'     => $p->collection_datetime ? $p->collection_datetime->format('d M Y') : $p->created_at->format('d M Y'),
                    'collection_time'     => $p->collection_datetime ? $p->collection_datetime->format('h:i A') : $p->created_at->format('h:i A'),
                    'confirm_url'         => route('admin.payments.confirm', $p->id),
                    'reject_url'          => route('admin.payments.reject', $p->id),
                ]),
                'allPayments' => $allPayments->map(fn($p) => [
                    'id'                  => $p->id,
                    'receipt_no'          => $p->receipt_no,
                    'party_name'          => $p->party?->name ?? '—',
                    'collected_by_name'   => $p->collectedBy?->name_with_role ?? '—',
                    'amount'              => (float) $p->amount,
                    'amount_formatted'    => number_format((float) $p->amount, 2),
                    'payment_mode'        => $p->payment_mode,
                    'status'              => $p->status,
                    'collection_datetime' => $p->collection_datetime ? $p->collection_datetime->format('d M Y, h:i A') : $p->created_at->format('d M Y, h:i A'),
                    'confirmed_by_name'   => $p->confirmedBy?->name_with_role,
                    'confirmed_at'        => $p->confirmed_at?->format('d M Y, h:i A'),
                    'rejection_reason'    => $p->rejection_reason,
                ]),
                'repSummaries' => $repSummaries->map(fn($rs) => [
                    'rep_name'      => $rs->collectedBy?->name_with_role ?? 'Unknown Rep',
                    'pending_count' => (int) $rs->pending_count,
                    'pending_total' => number_format((float) $rs->pending_total, 2),
                ]),
                'pagination' => [
                    'current_page' => $allPayments->currentPage(),
                    'last_page'    => $allPayments->lastPage(),
                    'per_page'     => $allPayments->perPage(),
                    'total'        => $allPayments->total(),
                    'from'         => $allPayments->firstItem() ?? 0,
                    'to'           => $allPayments->lastItem() ?? 0,
                ],
            ]);
        }

        return view('admin.payments.index', compact(
            'pendingPayments',
            'allPayments',
            'repSummaries',
            'parties',
            'reps',
            'activeTab',
            'status',
            'partyId',
            'collectedBy',
            'search'
        ));
    }

    public function confirm(Request $request, Payment $payment)
    {
        $scopedUserIds = DataScope::visibleUserIds('payments');
        if ($scopedUserIds !== null && !in_array($payment->collected_by, $scopedUserIds, true)) {
            abort(403, 'Unauthorized to confirm this payment.');
        }

        if ($payment->status !== 'pending') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Payment is already {$payment->status}."], 422);
            }
            return back()->with('error', "Payment is already {$payment->status}.");
        }

        try {
            DB::transaction(function () use ($payment, $request) {
                $payment->update([
                    'status'       => 'confirmed',
                    'confirmed_by' => auth()->id(),
                    'confirmed_at' => now(),
                ]);

                // Create Transaction in Customer Ledger
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

                // Audit Log
                AuditLog::logEvent(
                    event: 'payment.confirmed',
                    auditable: $payment,
                    oldValues: ['status' => 'pending'],
                    newValues: [
                        'status'       => 'confirmed',
                        'confirmed_by' => auth()->id(),
                        'amount'       => $payment->amount,
                    ],
                    reason: 'Admin confirmed payment received via Web Admin'
                );
            });

            // Notify Rep
            if ($payment->collectedBy) {
                try {
                    $payment->collectedBy->notify(new PaymentConfirmedNotification($payment));
                } catch (\Throwable $e) {
                    Log::warning("Failed to notify rep of confirmation: {$e->getMessage()}");
                }
            }

            $successMsg = "Payment {$payment->receipt_no} confirmed and credited to {$payment->party?->name}'s ledger.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $successMsg]);
            }

            return back()->with('success', $successMsg);

        } catch (\Throwable $e) {
            Log::error("Web Payment confirmation failed: {$e->getMessage()}");
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Confirmation failed: {$e->getMessage()}"], 500);
            }
            return back()->with('error', "Confirmation failed: {$e->getMessage()}");
        }
    }

    public function reject(Request $request, Payment $payment)
    {
        $scopedUserIds = DataScope::visibleUserIds('payments');
        if ($scopedUserIds !== null && !in_array($payment->collected_by, $scopedUserIds, true)) {
            abort(403, 'Unauthorized to reject this payment.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|min:3|max:1000',
        ]);

        if ($payment->status !== 'pending') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Payment is already {$payment->status}."], 422);
            }
            return back()->with('error', "Payment is already {$payment->status}.");
        }

        try {
            DB::transaction(function () use ($payment, $request) {
                $payment->update([
                    'status'           => 'rejected',
                    'confirmed_by'     => auth()->id(),
                    'confirmed_at'     => now(),
                    'rejection_reason' => $request->rejection_reason,
                ]);

                AuditLog::logEvent(
                    event: 'payment.rejected',
                    auditable: $payment,
                    oldValues: ['status' => 'pending'],
                    newValues: [
                        'status'           => 'rejected',
                        'confirmed_by'     => auth()->id(),
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
                    Log::warning("Failed to notify rep of rejection: {$e->getMessage()}");
                }
            }

            $successMsg = "Payment {$payment->receipt_no} has been rejected.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $successMsg]);
            }

            return back()->with('success', $successMsg);

        } catch (\Throwable $e) {
            Log::error("Web Payment rejection failed: {$e->getMessage()}");
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Rejection failed: {$e->getMessage()}"], 500);
            }
            return back()->with('error', "Rejection failed: {$e->getMessage()}");
        }
    }
}
