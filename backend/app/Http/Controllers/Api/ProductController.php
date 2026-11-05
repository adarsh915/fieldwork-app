<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get list of all products with their variations (units).
     * 
     * PERFORMANCE FIX: Added pagination and search functionality
     * - Supports pagination (default 50 products, max 100)
     * - Supports search by name/code
     * - Supports category filtering
     * - Optimized with selective column fetching
     */
    public function index(Request $request)
    {
        // Pagination with reasonable limits (max 100 products per page)
        $perPage = min(100, max(1, $request->get('per_page', 50)));
        
        $query = Product::query()
            ->select(['id', 'name', 'product_code', 'category_id', 'price', 'status', 'image'])
            ->with(['units:id,product_id,unit_name,conversion_factor,price'])
            ->where('status', 'active');
        
        // Search functionality (name or product code)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('product_code', 'LIKE', "%{$search}%");
            });
        }
        
        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Support for "all products" if explicitly requested (with safety limit)
        if ($request->get('all') === 'true' || $request->get('all') === '1') {
            // Still enforce a reasonable limit even for "all"
            $products = $query->orderBy('name')->limit(500)->get();
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'total' => $products->count(),
            ]);
        }
        
        // Paginated response (default)
        $products = $query->orderBy('name')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
                'has_more' => $products->hasMorePages(),
            ],
        ]);
    }
}
