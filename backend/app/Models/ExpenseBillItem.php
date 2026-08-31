<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseBillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_bill_id',
        'item_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function expenseBill()
    {
        return $this->belongsTo(ExpenseBill::class, 'expense_bill_id');
    }
}
