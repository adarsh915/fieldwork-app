<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseBill;
use App\Models\ExpenseCategory;
use App\Models\DayStart;
use App\Services\ActivityNotificationService;
use App\Services\DataScope;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Ultra-fast single aggregate query for tab badge counts (uses composite index)
        $statusCounts = ExpenseBill::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_all,
                SUM(CASE WHEN status IN ("pending", "temp_approved") THEN 1 ELSE 0 END) as total_pending,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as total_approved,
                SUM(CASE WHEN status IN ("rejected", "manager_rejected") THEN 1 ELSE 0 END) as total_rejected
            ')
            ->first();

        $query = ExpenseBill::query()
            ->where('user_id', $userId)
            ->with([
                'category:id,name',
                'items:id,expense_bill_id,item_name,quantity,unit_price,total_price',
                'rejectedBy:id,name',
                'approvedBy:id,name',
                'tempApprovedBy:id,name'
            ]);

        // Status filtering matching mobile UI tab categories
        if ($request->filled('status') && $request->status !== 'all') {
            $status = strtolower(trim($request->status));
            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'temp_approved']);
            } elseif ($status === 'approved') {
                $query->where('status', 'approved');
            } elseif ($status === 'rejected') {
                $query->whereIn('status', ['rejected', 'manager_rejected']);
            } else {
                $query->where('status', $status);
            }
        }

        // Category filter if specified
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search in reason, notes, amount, category name, or item name
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('rejection_reason', 'LIKE', "%{$search}%")
                  ->orWhere('final_note', 'LIKE', "%{$search}%")
                  ->orWhere('temp_approved_note', 'LIKE', "%{$search}%")
                  ->orWhere('amount', 'LIKE', "%{$search}%")
                  ->orWhereHas('category', function ($catQ) use ($search) {
                      $catQ->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('items', function ($itemQ) use ($search) {
                      $itemQ->where('item_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $query->orderBy('date', 'desc')
              ->orderBy('id', 'desc');

        $countsArray = [
            'all'      => (int) ($statusCounts->total_all ?? 0),
            'pending'  => (int) ($statusCounts->total_pending ?? 0),
            'approved' => (int) ($statusCounts->total_approved ?? 0),
            'rejected' => (int) ($statusCounts->total_rejected ?? 0),
        ];

        // Server-side pagination
        if ($request->has('page') || $request->has('per_page')) {
            $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
            $paginated = $query->paginate($perPage);

            return response()->json([
                'success'      => true,
                'data'         => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'has_more'     => $paginated->hasMorePages(),
                'counts'       => $countsArray,
            ]);
        }

        $expenses = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $expenses,
            'total'   => $expenses->count(),
            'counts'  => $countsArray,
        ]);
    }

    public function monthlySummary(Request $request): JsonResponse
    {
        $expenses = ExpenseBill::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($query) {
                $query->where('status', 'approved')
                    ->orWhere(function ($sq) {
                        $sq->where('is_system_generated', true)
                            ->whereNotIn('status', ['rejected', 'manager_rejected']);
                    });
            })
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, SUM(amount) as total_approved')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $expenses]);
    }

    public function statement(Request $request): JsonResponse
    {
        $monthInput = $request->input('month', Carbon::now()->format('Y-m'));
        $month = Carbon::parse($monthInput);
        $user_id = $request->user()->id;

        $expenses = ExpenseBill::with('category')
            ->where('user_id', $user_id)
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->where(function ($query) {
                $query->where('status', 'approved')
                    ->orWhere(function ($sq) {
                        $sq->where('is_system_generated', true)
                            ->whereNotIn('status', ['rejected', 'manager_rejected']);
                    });
            })
            ->orderBy('date', 'asc')
            ->get();

        $groupedByDate = $expenses->groupBy(function ($expense) {
            return Carbon::parse($expense->date)->format('Y-m-d');
        });

        $statementData = $groupedByDate->map(function ($dayExpenses, $dateString) {
            $foodTotal = $dayExpenses->filter(fn($e) => optional($e->category)->name === 'Food' && !$e->is_system_generated)->sum('amount');
            $hotelTotal = $dayExpenses->filter(fn($e) => optional($e->category)->name === 'Hotel' && !$e->is_system_generated)->sum('amount');
            $travelTotal = $dayExpenses->filter(fn($e) => optional($e->category)->name === 'Travel' && !$e->is_system_generated)->sum('amount');
            $miscTotal = $dayExpenses->filter(fn($e) => optional($e->category)->name === 'Misc' && !$e->is_system_generated)->sum('amount');
            $otherManualTotal = $dayExpenses->filter(fn($e) => !$e->is_system_generated && !in_array(optional($e->category)->name, ['Food', 'Hotel', 'Travel', 'Misc']))->sum('amount');
            $systemTravelTotal = $dayExpenses->filter(fn($e) => $e->is_system_generated)->sum('amount');

            return [
                'date' => Carbon::parse($dateString)->format('d M Y'),
                'food' => (float) $foodTotal,
                'hotel' => (float) $hotelTotal,
                'travel' => (float) $travelTotal,
                'misc' => (float) $miscTotal,
                'other' => (float) $otherManualTotal,
                'system_travel' => (float) $systemTravelTotal,
                'day_total' => (float) ($foodTotal + $hotelTotal + $travelTotal + $miscTotal + $otherManualTotal + $systemTravelTotal),
            ];
        })->values();

        $totals = [
            'food' => (float) $statementData->sum('food'),
            'hotel' => (float) $statementData->sum('hotel'),
            'travel' => (float) $statementData->sum('travel'),
            'misc' => (float) $statementData->sum('misc'),
            'other' => (float) $statementData->sum('other'),
            'system_travel' => (float) $statementData->sum('system_travel'),
            'grand_total' => (float) $statementData->sum('day_total'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'statementData' => $statementData,
                'totals' => $totals
            ]
        ]);
    }

    public function categories(SettingsService $settings): JsonResponse
    {
        $categories = ExpenseCategory::where('is_active', true)->get();
        $pastDaysAllowed = (int) $settings->get('expense_past_days_allowed', 7);

        return response()->json([
            'success' => true,
            'data' => $categories,
            'past_days_allowed' => $pastDaysAllowed,
        ]);
    }

    public function store(Request $request, SettingsService $settings): JsonResponse
    {
        $user = $request->user();
        if (!$user->can('expenses.create') && !$user->hasRole(['Super Admin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to submit expenses.',
            ], 403);
        }

        $today = Carbon::today()->toDateString();

        $pastDaysLimit = (int) $settings->get('expense_past_days_allowed', 7);
        $minAllowedDate = Carbon::today()->subDays($pastDaysLimit)->toDateString();

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'bill_photo' => 'required|image|max:20480',
            'date' => [
                'nullable',
                'date_format:Y-m-d',
                "after_or_equal:{$minAllowedDate}",
                "before_or_equal:{$today}",
            ],
        ], [
            'date.after_or_equal' => "Expenses cannot be backdated more than {$pastDaysLimit} days in the past.",
            'date.before_or_equal' => 'Future expense dates are not allowed.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $expenseDate = $request->filled('date') ? Carbon::parse($request->input('date'))->toDateString() : $today;

        // Check if DayStart record exists for this user and date
        $dayStart = DayStart::where('user_id', $user->id)
            ->where('date', $expenseDate)
            ->first();

        // Determine if expense is logged after day end or backdated
        $isAfterDayEnd = false;
        if ($expenseDate < $today) {
            // Any past date submission is backdated / post day-end
            $isAfterDayEnd = true;
        } elseif ($dayStart && ($dayStart->end_time !== null || $dayStart->status === 'completed')) {
            // Submitted today after Day End was logged
            $isAfterDayEnd = true;
        } elseif (!$dayStart) {
            // Submitted today without an active DayStart
            $isAfterDayEnd = true;
        }

        $items = [];
        if ($request->filled('items_json')) {
            $items = json_decode($request->input('items_json'), true);
            if (!is_array($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'items_json must be a valid JSON array',
                ], 422);
            }

            $itemsValidator = Validator::make(['items' => $items], [
                'items' => 'required|array|min:1',
                'items.*.item_name' => 'required|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            if ($itemsValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Items validation failed',
                    'errors' => $itemsValidator->errors()
                ], 422);
            }
        }

        $imagePath = $request->file('bill_photo')->store('expenses', 'public');

        $expense = ExpenseBill::create([
            'user_id' => $user->id,
            'day_start_id' => $dayStart?->id,
            'category_id' => $request->category_id,
            'date' => $expenseDate,
            'amount' => $request->amount,
            'bill_photo' => $imagePath,
            'status' => 'pending',
            'is_system_generated' => false,
            'is_after_day_end' => $isAfterDayEnd,
        ]);

        if (!empty($items)) {
            foreach ($items as $item) {
                $expense->items()->create([
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        }

        $expense->load(['category', 'items']);

        ActivityNotificationService::notifyExpenseSubmitted($expense);

        return response()->json([
            'success' => true,
            'message' => $isAfterDayEnd
                ? 'Expense submitted successfully (marked as post day-end / backdated for HR review).'
                : 'Expense submitted successfully.',
            'data' => $expense
        ], 201);
    }

    public function update(Request $request, ExpenseBill $expense, SettingsService $settings): JsonResponse
    {
        $user = $request->user();

        if ($expense->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit this expense.'
            ], 403);
        }

        if ($expense->is_system_generated) {
            return response()->json([
                'success' => false,
                'message' => 'System travel expenses cannot be manually edited.'
            ], 422);
        }

        if ($expense->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Approved expenses cannot be edited.'
            ], 422);
        }

        $today = Carbon::today()->toDateString();
        $pastDaysLimit = (int) $settings->get('expense_past_days_allowed', 7);
        $minAllowedDate = Carbon::today()->subDays($pastDaysLimit)->toDateString();

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'bill_photo' => 'nullable|image|max:20480',
            'date' => [
                'nullable',
                'date_format:Y-m-d',
                "after_or_equal:{$minAllowedDate}",
                "before_or_equal:{$today}",
            ],
        ], [
            'date.after_or_equal' => "Expenses cannot be backdated more than {$pastDaysLimit} days in the past.",
            'date.before_or_equal' => 'Future expense dates are not allowed.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $expenseDate = $request->filled('date') ? Carbon::parse($request->input('date'))->toDateString() : ($expense->date ? $expense->date->toDateString() : $today);

        $items = [];
        if ($request->filled('items_json')) {
            $items = json_decode($request->input('items_json'), true);
            if (!is_array($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'items_json must be a valid JSON array',
                ], 422);
            }

            $itemsValidator = Validator::make(['items' => $items], [
                'items' => 'required|array|min:1',
                'items.*.item_name' => 'required|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            if ($itemsValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Items validation failed',
                    'errors' => $itemsValidator->errors()
                ], 422);
            }
        }

        $updateData = [
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'date' => $expenseDate,
            'status' => 'pending',
            'rejection_reason' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'temp_approved_by' => null,
            'temp_approved_at' => null,
            'temp_approved_note' => null,
            'final_note' => null,
        ];

        if ($request->hasFile('bill_photo')) {
            $updateData['bill_photo'] = $request->file('bill_photo')->store('expenses', 'public');
        }

        $oldAmount = (float) $expense->amount;

        $expense->update($updateData);

        if (!empty($items)) {
            $expense->items()->delete();
            foreach ($items as $item) {
                $expense->items()->create([
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        }

        $expense->load(['category', 'items']);

        ActivityNotificationService::notifyExpenseUpdated($expense, $oldAmount, $user);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated and resubmitted successfully.',
            'data' => $expense
        ]);
    }
}
