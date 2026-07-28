<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ProductController — CRUD for Products with multiple units & prices.
 */
class ProductController extends Controller
{
    /**
     * List all products with search and status filters.
     */
    public function index(Request $request)
    {
        $search = $request->input('q');
        $status = $request->input('status');

        $query = Product::with('units')
            ->withCount('units')
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $products   = $query->paginate(20)->withQueryString();
        $categories = [];

        return view('admin.products.index', compact('products', 'categories', 'search', 'status'));
    }

    /**
     * Show create product form.
     */
    public function create()
    {
        $categories = [];

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a new product with its units.
     *
     * Expects:
     *   name, code?, description?, status
     *   units[0][unit_name], units[0][price], units[0][is_default]
     *   units[1][unit_name], units[1][price], ...
     *   attributes[] (array of attribute IDs)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'code'                  => ['nullable', 'string', 'max:100', 'unique:products,code'],
            'description'           => ['nullable', 'string', 'max:2000'],
            'status'                => ['required', 'in:active,inactive'],
            'units'                 => ['required', 'array', 'min:1'],
            'units.*.unit_name'     => ['required', 'string', 'max:100'],
            'units.*.price'         => ['required', 'numeric', 'min:0'],
            'units.*.is_default'    => ['nullable'],
            'attributes'            => ['nullable', 'array'],
            'attributes.*'          => ['numeric', 'exists:product_attributes,id'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $product = Product::create([
                'name'        => $validated['name'],
                'code'        => $validated['code']        ?? null,
                'description' => $validated['description'] ?? null,
                'status'      => $validated['status'],
                'created_by'  => auth()->id(),
                'updated_by'  => auth()->id(),
            ]);

            // Determine which index is the default unit
            $defaultIndex = $request->input('default_unit_index', 0);

            foreach ($validated['units'] as $i => $unit) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_name'  => trim($unit['unit_name']),
                    'price'      => $unit['price'],
                    'is_default' => ((int) $defaultIndex === (int) $i),
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Sync attributes to product
            if (!empty($validated['attributes'])) {
                $product->attributes()->sync($validated['attributes']);
            }
        });

        return redirect()->route('admin.products.index')
            ->with('status', 'Product "' . $validated['name'] . '" created successfully!');
    }

    /**
     * Show edit product form.
     */
    public function edit($id)
    {
        $product    = Product::with('units', 'images', 'attributes.values', 'variants')->findOrFail($id);
        $categories = [];

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update a product and fully replace its units.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'code'                  => ['nullable', 'string', 'max:100', "unique:products,code,{$product->id}"],
            'description'           => ['nullable', 'string', 'max:2000'],
            'status'                => ['required', 'in:active,inactive'],
            'units'                 => ['required', 'array', 'min:1'],
            'units.*.unit_name'     => ['required', 'string', 'max:100'],
            'units.*.price'         => ['required', 'numeric', 'min:0'],
            'units.*.is_default'    => ['nullable'],
            'attributes'            => ['nullable', 'array'],
            'attributes.*'          => ['numeric', 'exists:product_attributes,id'],
        ]);

        DB::transaction(function () use ($product, $validated, $request) {
            $product->update([
                'name'        => $validated['name'],
                'code'        => $validated['code']        ?? null,
                'description' => $validated['description'] ?? null,
                'status'      => $validated['status'],
                'updated_by'  => auth()->id(),
            ]);

            // Delete existing units and re-create (simplest correct approach)
            $product->units()->delete();

            $defaultIndex = $request->input('default_unit_index', 0);

            foreach ($validated['units'] as $i => $unit) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_name'  => trim($unit['unit_name']),
                    'price'      => $unit['price'],
                    'is_default' => ((int) $defaultIndex === (int) $i),
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Sync attributes to product
            if (!empty($validated['attributes'])) {
                $product->attributes()->sync($validated['attributes']);
            } else {
                $product->attributes()->detach();
            }
        });

        return redirect()->route('admin.products.index')
            ->with('status', 'Product "' . $product->name . '" updated successfully!');
    }

    /**
     * Soft-delete a product (units cascade via DB).
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $name    = $product->name;
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('status', 'Product "' . $name . '" deleted successfully!');
    }

    /**
     * Toggle product active/inactive status.
     */
    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->update([
            'status'     => $product->status === Product::STATUS_ACTIVE
                                ? Product::STATUS_INACTIVE
                                : Product::STATUS_ACTIVE,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('status', 'Product status updated successfully!');
    }
}
