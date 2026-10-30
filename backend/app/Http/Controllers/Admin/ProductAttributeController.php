<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductAttributeController extends Controller
{
    /**
     * List all product attributes
     */
    public function index(Request $request)
    {
        $search = $request->input('q');
        $status = $request->input('status');

        $query = ProductAttribute::withCount('values', 'products');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('is_active', $status === 'active');
        }

        $attributes = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return view('admin.product-attributes.index', compact('attributes', 'search', 'status'));
    }

    /**
     * Show create attribute form
     */
    public function create()
    {
        $inputTypes = ['text' => 'Text Input', 'select' => 'Dropdown', 'multiselect' => 'Multi-Select', 'color' => 'Color Picker', 'date' => 'Date Picker'];
        return view('admin.product-attributes.create', compact('inputTypes'));
    }

    /**
     * Store a new attribute
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:product_attributes,name'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:product_attributes,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'input_type'  => ['required', 'in:text,select,multiselect,color,date'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $request->has('is_active');

        $attribute = ProductAttribute::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Attribute \"{$validated['name']}\" created successfully!",
                'attribute' => $attribute,
                'redirect_url' => route('admin.product-attributes.index'),
            ]);
        }

        return redirect()->route('admin.product-attributes.index')
            ->with('status', "Attribute \"{$validated['name']}\" created successfully!");
    }

    /**
     * Show edit attribute form
     */
    public function edit($id)
    {
        $attribute = ProductAttribute::with('values')->findOrFail($id);
        $inputTypes = ['text' => 'Text Input', 'select' => 'Dropdown', 'multiselect' => 'Multi-Select', 'color' => 'Color Picker', 'date' => 'Date Picker'];
        return view('admin.product-attributes.edit', compact('attribute', 'inputTypes'));
    }

    /**
     * Update an attribute
     */
    public function update(Request $request, $id)
    {
        $attribute = ProductAttribute::findOrFail($id);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', "unique:product_attributes,name,{$id}"],
            'description' => ['nullable', 'string', 'max:1000'],
            'input_type'  => ['required', 'in:text,select,multiselect,color,date'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->has('is_active');

        $attribute->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Attribute \"{$attribute->name}\" updated successfully!",
                'attribute' => $attribute,
            ]);
        }

        return redirect()->route('admin.product-attributes.index')
            ->with('status', "Attribute \"{$attribute->name}\" updated successfully!");
    }

    /**
     * Delete an attribute
     */
    public function destroy(Request $request, $id)
    {
        $attribute = ProductAttribute::findOrFail($id);
        $name = $attribute->name;
        $attribute->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Attribute \"{$name}\" deleted successfully!",
                'redirect_url' => route('admin.product-attributes.index'),
            ]);
        }

        return redirect()->route('admin.product-attributes.index')
            ->with('status', "Attribute \"{$name}\" deleted successfully!");
    }

    /**
     * Add a value to an attribute
     */
    public function addValue(Request $request, $attributeId)
    {
        $attribute = ProductAttribute::findOrFail($attributeId);

        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255', "unique:product_attribute_values,value,NULL,id,product_attribute_id,{$attributeId}"],
            'color_code' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $validated['product_attribute_id'] = $attributeId;
        $validated['created_by'] = auth()->id();

        $val = ProductAttributeValue::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Value \"{$validated['value']}\" added successfully!",
                'value' => $val,
            ]);
        }

        return redirect()->route('admin.product-attributes.edit', $attributeId)
            ->with('status', "Value \"{$validated['value']}\" added successfully!");
    }

    /**
     * Delete an attribute value
     */
    public function deleteValue(Request $request, $valueId)
    {
        $value = ProductAttributeValue::findOrFail($valueId);
        $attributeId = $value->product_attribute_id;
        $valueName = $value->value;
        $value->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Value \"{$valueName}\" deleted successfully!",
            ]);
        }

        return redirect()->route('admin.product-attributes.edit', $attributeId)
            ->with('status', "Value \"{$valueName}\" deleted successfully!");
    }
}
