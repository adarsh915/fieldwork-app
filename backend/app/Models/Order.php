<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_no', 'party_id', 'user_id', 'visit_id', 'order_date', 
        'total_amount', 'status', 'remarks', 'delivery_destination',
        'preferred_transport', 'vehicle_no', 'dispatch_date', 'schema',
        'order_type', 'payment_mode'
    ];

    protected $casts = [
        'dispatch_date' => 'date',
        'total_amount' => 'decimal:2',
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
