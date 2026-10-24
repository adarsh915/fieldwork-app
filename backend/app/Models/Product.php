<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Product extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = ['company_id', 'category_id', 'name', 'code', 'unit', 'price', 'status', 'hsn_code', 'gst_percent', 'created_by', 'updated_by', 'image'];

    // ── Relationships ──────────────────────────────────────────────────────────

    /**
     * All pricing units for this product (Piece, Box, Carton, Kg, etc.)
     */
    public function units()
    {
        return $this->hasMany(ProductUnit::class)->orderByDesc('is_default')->orderBy('unit_name');
    }

    /**
     * The default/primary selling unit.
     */
    public function defaultUnit()
    {
        return $this->hasOne(ProductUnit::class)->where('is_default', true);
    }

    /**
     * Product images gallery
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Featured product image
     */
    public function featuredImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_featured', true);
    }

    /**
     * Product variants (e.g., Red Small T-Shirt, Blue Large T-Shirt)
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Attributes applied to this product
     */
    public function attributes()
    {
        return $this->belongsToMany(
            ProductAttribute::class,
            'product_has_attributes',
            'product_id',
            'product_attribute_id'
        )->orderBy('sort_order');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
