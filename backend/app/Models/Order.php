<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Order extends Model
{
    use Auditable;

    protected $fillable = [
        'order_no', 'party_id', 'user_id', 'visit_id', 'order_date', 
        'total_amount', 'status', 'order_source', 'is_after_day_end',
        'remarks', 'delivery_destination',
        'preferred_transport', 'vehicle_no', 'dispatch_date', 'schema',
        'order_type', 'payment_mode'
    ];

    protected $casts = [
        'order_date' => 'date',
        'dispatch_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_after_day_end' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function party()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
