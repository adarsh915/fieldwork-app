<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'description',
        'price',
        'cost_price',
        'stock_quantity',
        'track_inventory',
        'image',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'track_inventory' => 'boolean',
    ];

    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';

    // ── Relationships ──────────────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Attributes of this variant (Color: Red, Size: Small, etc.)
     */
    public function attributes()
    {
        return $this->belongsToMany(
            ProductAttribute::class,
            'product_variant_attributes',
            'product_variant_id',
            'product_attribute_id'
        )->using(ProductVariantAttribute::class)
         ->withPivot('product_attribute_value_id');
    }

    /**
     * Get attribute mappings (for easier data access)
     */
    public function variantAttributes()
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // ── Methods ──────────────────────────────────────────────────────────────────

    /**
     * Get attribute values as a formatted string (e.g., "Red, Small, Cotton")
     */
    public function getAttributesAsStringAttribute()
    {
        return $this->variantAttributes()
            ->with('productAttributeValue')
            ->get()
            ->map(fn ($attr) => $attr->productAttributeValue->value)
            ->join(', ');
    }

    /**
     * Get the final price (use variant price or fallback to product default unit price)
     */
    public function getFinalPriceAttribute()
    {
        return $this->price ?? $this->product->defaultUnit?->price ?? 0;
    }

    /**
     * Check if in stock
     */
    public function getIsInStockAttribute()
    {
        return !$this->track_inventory || $this->stock_quantity > 0;
    }
}
