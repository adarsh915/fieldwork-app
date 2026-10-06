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

        if ($status && in_array($status, ['active', 'inactive'])) {
            $query->where('status', $status);
        }

        $totalCount    = Product::count();
        $activeCount   = Product::where('status', 'active')->count();
        $inactiveCount = Product::where('status', 'inactive')->count();

        $products = $query->paginate(20)->withQueryString();
        $formattedProducts = $products->map(fn ($p) => $this->formatProduct($p));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'products'   => $formattedProducts,
                'stats'      => [
                    'total'    => $totalCount,
                    'active'   => $activeCount,
                    'inactive' => $inactiveCount,
                ],
                'pagination' => [
                    'total'        => $products->total(),
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'from'         => $products->firstItem(),
                    'to'           => $products->lastItem(),
                ],
            ]);
        }

        $categories = [];

        return view('admin.products.index', compact(
            'products',
            'formattedProducts',
            'categories',
            'search',
            'status',
            'totalCount',
            'activeCount',
            'inactiveCount'
        ));
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

        $product = null;

        DB::transaction(function () use ($validated, $request, &$product) {
            $product = Product::create([
                'name'        => $validated['name'],
                'code'        => $validated['code']        ?? null,
                'description' => $validated['description'] ?? null,
                'status'      => $validated['status'],
                'created_by'  => auth()->id(),
                'updated_by'  => auth()->id(),
            ]);

            $defaultIndex = $request->input('default_unit_index', 0);

            foreach ($validated['units'] as $i => $unit) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_name'  => trim($unit['unit_name']),
                    'price'      => $unit['price'],
                    'is_default' => ((int) $defaultIndex === (int) $i || !empty($unit['is_default'])),
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            if (!empty($validated['attributes'])) {
                $product->attributes()->sync($validated['attributes']);
            }
        });

        $product->load('units');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product "' . $product->name . '" created successfully!',
                'product' => $this->formatProduct($product),
            ]);
        }

        return redirect()->route('admin.products.index')
            ->with('status', 'Product "' . $validated['name'] . '" created successfully!');
    }

    /**
     * Show edit product form.
     */
    public function edit($id)
    {
        $product = Product::with('units', 'images', 'attributes.values', 'variants')->findOrFail($id);
        $categories = [];

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'product' => $this->formatProduct($product),
            ]);
        }

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

            $product->units()->delete();

            $defaultIndex = $request->input('default_unit_index', 0);

            foreach ($validated['units'] as $i => $unit) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_name'  => trim($unit['unit_name']),
                    'price'      => $unit['price'],
                    'is_default' => ((int) $defaultIndex === (int) $i || !empty($unit['is_default'])),
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            if (!empty($validated['attributes'])) {
                $product->attributes()->sync($validated['attributes']);
            } else {
                $product->attributes()->detach();
            }
        });

        $product->load('units');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product "' . $product->name . '" updated successfully!',
                'product' => $this->formatProduct($product),
            ]);
        }

        return redirect()->route('admin.products.index')
            ->with('status', 'Product "' . $product->name . '" updated successfully!');
    }

    /**
     * Soft-delete a product (units cascade via DB).
     */
    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $name    = $product->name;
        $product->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'id'      => (int) $id,
                'message' => 'Product "' . $name . '" deleted successfully!',
            ]);
        }

        return redirect()->route('admin.products.index')
            ->with('status', 'Product "' . $name . '" deleted successfully!');
    }

    /**
     * Toggle product active/inactive status.
     */
    public function toggleStatus(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $newStatus = $product->status === Product::STATUS_ACTIVE
            ? Product::STATUS_INACTIVE
            : Product::STATUS_ACTIVE;

        $product->update([
            'status'     => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        $product->load('units');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status'  => $newStatus,
                'product' => $this->formatProduct($product),
                'message' => 'Product "' . $product->name . '" is now ' . $newStatus . '.',
            ]);
        }

        return back()->with('status', 'Product status updated successfully!');
    }

    /**
     * Helper to format product for response.
     */
    private function formatProduct(Product $p): array
    {
        return [
            'id'          => $p->id,
            'name'        => $p->name,
            'code'        => $p->code ?? '',
            'category'    => $p->category ?? '—',
            'status'      => $p->status,
            'description' => $p->description ?? '',
            'units'       => $p->units->map(function ($u) {
                return [
                    'id'              => $u->id,
                    'unit_name'       => $u->unit_name,
                    'price'           => (float) $u->price,
                    'price_formatted' => number_format((float) $u->price, 2),
                    'is_default'      => (bool) $u->is_default,
                ];
            })->toArray(),
        ];
    }
}
