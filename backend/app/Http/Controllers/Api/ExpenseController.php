<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseBill;
use App\Models\ExpenseCategory;
use App\Models\DayStart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $expenses = ExpenseBill::with('category')
            ->where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json(['success' => true, 'data' => $expenses]);
    }

    public function monthlySummary(Request $request): JsonResponse
    {
        // Group all approved expenses by month
        $expenses = ExpenseBill::where('user_id', $request->user()->id)
            
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

        $dayStarts = DayStart::with(['expenses' => function($q) {
            $q->where(function($query) {
                $query->where('status', 'approved')
                      ->orWhere('is_system_generated', true);
            });
        }, 'expenses.category'])
        ->where('user_id', $user_id)
        ->whereMonth('date', $month->month)
        ->whereYear('date', $month->year)
        ->orderBy('date', 'asc')
        ->get();

        $statementData = $dayStarts->map(function($dayStart) {
            $foodTotal = $dayStart->expenses->filter(fn($e) => optional($e->category)->name === 'Food' && !$e->is_system_generated)->sum('amount');
            $hotelTotal = $dayStart->expenses->filter(fn($e) => optional($e->category)->name === 'Hotel' && !$e->is_system_generated)->sum('amount');
            $travelTotal = $dayStart->expenses->filter(fn($e) => optional($e->category)->name === 'Travel' && !$e->is_system_generated)->sum('amount');
            $miscTotal = $dayStart->expenses->filter(fn($e) => optional($e->category)->name === 'Misc' && !$e->is_system_generated)->sum('amount');
            
            // For any category that might not match the defaults above
            $otherManualTotal = $dayStart->expenses->filter(fn($e) => !$e->is_system_generated && !in_array(optional($e->category)->name, ['Food', 'Hotel', 'Travel', 'Misc']))->sum('amount');
            
            $systemTravelTotal = $dayStart->expenses->filter(fn($e) => $e->is_system_generated)->sum('amount');

            return [
                'date' => Carbon::parse($dayStart->date)->format('d M Y'),
                'food' => $foodTotal,
                'hotel' => $hotelTotal,
                'travel' => $travelTotal,
                'misc' => $miscTotal,
                'other' => $otherManualTotal,
                'system_travel' => $systemTravelTotal,
                'day_total' => $foodTotal + $hotelTotal + $travelTotal + $miscTotal + $otherManualTotal + $systemTravelTotal,
            ];
        });

        $totals = [
            'food' => $statementData->sum('food'),
            'hotel' => $statementData->sum('hotel'),
            'travel' => $statementData->sum('travel'),
            'misc' => $statementData->sum('misc'),
            'other' => $statementData->sum('other'),
            'system_travel' => $statementData->sum('system_travel'),
            'grand_total' => $statementData->sum('day_total'),
        ];

        return response()->json([
            'success' => true, 
            'data' => [
                'statementData' => $statementData,
                'totals' => $totals
            ]
        ]);
    }
    
    public function categories(): JsonResponse
    {
        $categories = ExpenseCategory::where('is_active', true)->get();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        
        $dayStart = DayStart::where('user_id', $user->id)->where('date', $today)->first();
        if (!$dayStart) {
            return response()->json([
                'success' => false,
                'message' => 'You must start your day before logging an expense.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'bill_photo' => 'required|image|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
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
            'day_start_id' => $dayStart->id,
            'category_id' => $request->category_id,
            'date' => $today,
            'amount' => $request->amount,
            'bill_photo' => $imagePath,
            'status' => 'pending',
            'is_system_generated' => false,
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

        return response()->json([
            'success' => true,
            'message' => 'Expense submitted successfully.',
            'data' => $expense
        ], 201);
    }
}
