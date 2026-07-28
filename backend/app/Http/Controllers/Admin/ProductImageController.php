<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    /**
     * Store a new product image
     */
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        if ($request->file('image')) {
            $path = $request->file('image')->store('products', 'public');

            $image = ProductImage::create([
                'product_id' => $productId,
                'path' => $path,
                'sort_order' => $product->images()->count(),
                'created_by' => auth()->id(),
            ]);

            // If this is the first image, make it featured
            if ($product->images()->count() === 1) {
                $image->update(['is_featured' => true]);
            }

            return response()->json([
                'success' => true,
                'image' => $image,
                'url' => asset('storage/' . $path),
            ]);
        }

        return response()->json(['success' => false], 400);
    }

    /**
     * Set as featured image
     */
    public function setFeatured($imageId)
    {
        $image = ProductImage::findOrFail($imageId);
        
        // Unset all featured for this product
        $image->product->images()->update(['is_featured' => false]);
        
        // Set this as featured
        $image->update(['is_featured' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete an image
     */
    public function destroy($imageId)
    {
        $image = ProductImage::findOrFail($imageId);
        $path = storage_path('app/public/' . $image->path);

        if (file_exists($path)) {
            unlink($path);
        }

        $image->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Reorder images
     */
    public function reorder(Request $request, $productId)
    {
        $request->validate([
            'order' => ['required', 'array'],
        ]);

        foreach ($request->order as $index => $imageId) {
            ProductImage::where('id', $imageId)
                ->where('product_id', $productId)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
