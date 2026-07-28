<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductVariantAttribute extends Pivot
{
    protected $table = 'variant_attributes';

    protected $fillable = [
        'product_variant_id',
        'product_attribute_id',
        'product_attribute_value_id',
    ];

    public $timestamps = false;

    // ── Relationships ──────────────────────────────────────────────────────────

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function productAttributeValue()
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }
}
