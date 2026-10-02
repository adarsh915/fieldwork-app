<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'value_type',
        'group',
        'description',
        'updated_by',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Cast raw setting_value string to typed PHP value.
     */
    public function getTypedValueAttribute()
    {
        return match ($this->value_type) {
            'number'  => is_numeric($this->setting_value) ? (float) $this->setting_value : 0,
            'boolean' => in_array(strtolower((string) $this->setting_value), ['1', 'true', 'yes', 'on']),
            'json'    => json_decode($this->setting_value, true) ?? [],
            default   => (string) $this->setting_value,
        };
    }
}
