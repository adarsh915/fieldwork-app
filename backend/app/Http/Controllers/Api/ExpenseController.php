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
            'bill_photo' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
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
        
        $expense->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Expense submitted successfully.',
            'data' => $expense
        ], 201);
    }
}
