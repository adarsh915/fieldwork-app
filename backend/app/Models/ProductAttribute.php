<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'input_type',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    /**
     * Attribute values (e.g., Red, Blue for Color)
     */
    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('sort_order');
    }

    /**
     * Products that use this attribute
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_has_attributes');
    }

    /**
     * Variants that use this attribute
     */
    public function variantAttributes()
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    // ── Mutators ────────────────────────────────────────────────────────────────

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // ── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Methods ──────────────────────────────────────────────────────────────────

    public function getInputTypeLabel()
    {
        return match($this->input_type) {
            'text' => 'Text Input',
            'select' => 'Dropdown',
            'multiselect' => 'Multi-Select',
            'color' => 'Color Picker',
            'date' => 'Date Picker',
            default => ucfirst($this->input_type),
        };
    }
}
