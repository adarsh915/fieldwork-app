<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'alt_text',
        'sort_order',
        'is_featured',
        'created_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }
}
