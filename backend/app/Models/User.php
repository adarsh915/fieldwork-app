<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\Auditable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Auditable;

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
        'profile_image',
        'reporting_to',
        'fcm_token',
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

    public function reportingManager()
    {
        return $this->belongsTo(User::class, 'reporting_to');
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'reporting_to');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function expenses()
    {
        return $this->hasMany(ExpenseBill::class);
    }

    public function dayStarts()
    {
        return $this->hasMany(DayStart::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function fuelRateOverrides()
    {
        return $this->hasMany(FuelAllowanceRate::class, 'scope_id')->where('scope_type', 'user');
    }

    public function territories()
    {
        return $this->hasMany(EmployeeTerritory::class, 'employee_id');
    }

    public function assignedDistricts()
    {
        return $this->belongsToMany(District::class, 'employee_territories', 'employee_id', 'district_id')
                    ->whereNull('employee_territories.area_id')
                    ->distinct();
    }

    public function assignedAreas()
    {
        return $this->belongsToMany(Area::class, 'employee_territories', 'employee_id', 'area_id')
                    ->whereNotNull('employee_territories.area_id')
                    ->distinct();
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

    protected $appends = [
        'role_name',
        'name_with_role',
        'profile_image_url',
    ];

    /**
     * Get the direct public URL for the employee's profile photo.
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if (!$this->profile_image) {
            return null;
        }

        if (filter_var($this->profile_image, FILTER_VALIDATE_URL)) {
            return $this->profile_image;
        }

        return asset('storage/' . $this->profile_image);
    }

    /**
     * Get the display role name for the user (e.g. "VP", "Manager", "Field Worker", "Admin").
     */
    public function getRoleNameAttribute(): string
    {
        $role = $this->roles->first()?->name ?? $this->position ?? 'Field Worker';
        if (strcasecmp($role, 'vp') === 0) {
            return 'VP';
        }
        return ucwords(str_replace(['_', '-'], ' ', $role));
    }

    /**
     * Get the formatted name with role in parentheses: "Name (Role)".
     */
    public function getNameWithRoleAttribute(): string
    {
        return "{$this->name} ({$this->role_name})";
    }
}
