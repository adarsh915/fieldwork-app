<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_attribute_id',
        'value',
        'slug',
        'color_code',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function variantAttributes()
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    // ── Mutators ────────────────────────────────────────────────────────────────

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->value);
            }
        });
    }
}
