<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_name',
        'price',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'is_default' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
