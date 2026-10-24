<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

/**
 * Dealer Model
 * Represents dealers/distributors managed by salesmen/managers
 */
class Dealer extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'dealer_code',
        'name',
        'company_name',
        'contact_person',
        'mobile',
        'alternate_mobile',
        'email',
        'whatsapp',
        'address',
        'city',
        'state',
        'pincode',
        'credit_limit',
        'outstanding_balance',
        'balance_type',
        'status',
        'dealer_type',
        'notes',
        'gst_number',
        'pan_number',
        'assigned_to',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Dealer type constants
     */
    const TYPE_DISTRIBUTOR = 'distributor';
    const TYPE_RETAILER = 'retailer';
    const TYPE_WHOLESALER = 'wholesaler';
    const TYPE_DEALER = 'dealer';

    /**
     * Balance type constants
     */
    const BALANCE_DEBIT = 'Db';
    const BALANCE_CREDIT = 'Cr';

    /**
     * Get the user (salesman/manager) assigned to this dealer
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this dealer
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this dealer
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get only active dealers
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get dealers assigned to a specific user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to filter by city
     */
    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope to filter by dealer type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('dealer_type', $type);
    }

    /**
     * Get formatted balance with type
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->outstanding_balance, 2) . ' ' . $this->balance_type;
    }
}
