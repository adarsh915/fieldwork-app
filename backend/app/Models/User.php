<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'employee_id',
        'address',
        'date_of_birth',
        'hire_date',
        'department',
        'position',
        'salary',
        'is_active',
        'reporting_to',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'reporting_to');
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'reporting_to');
    }

    public function dayStarts()
    {
        return $this->hasMany(DayStart::class);
    }

    public function parties()
    {
        return $this->belongsToMany(Customer::class, 'party_employee_map', 'employee_id', 'party_id')
                    ->wherePivot('is_active', true)
                    ->withTimestamps();
    }

    public function hasParty(int $customerId): bool
    {
        return $this->parties()->where('customers.id', $customerId)->exists();
    }

    public function canSelectAllParties(): bool
    {
        return $this->hasAnyRole(['admin', 'Admin', 'Super Admin']);
    }
}
