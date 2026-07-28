<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get list of all products with their variations (units).
     */
    public function index(Request $request)
    {
        $products = Product::with('units')->where('status', 'active')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
