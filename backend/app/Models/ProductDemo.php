<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductDemo extends Model {
    protected $fillable = ['user_id', 'party_id', 'product_id', 'date', 'feedback'];
    protected $casts = ['date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
    public function party() { return $this->belongsTo(Customer::class, 'party_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}