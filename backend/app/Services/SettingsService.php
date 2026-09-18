<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Initial default settings matrix
     */
    private const DEFAULTS = [
        'full_day_hours_threshold'       => ['value' => '8',        'type' => 'number',  'group' => 'attendance', 'description' => 'Working hours required for Full-Day Present'],
        'half_day_hours_threshold'       => ['value' => '4',        'type' => 'number',  'group' => 'attendance', 'description' => 'Working hours required for Half-Day Present'],
        'auto_close_time'                => ['value' => '23:59:59', 'type' => 'string',  'group' => 'attendance', 'description' => 'Time when missing DayEnd records are auto-closed'],
        'retroactive_leave_days_allowed' => ['value' => '7',        'type' => 'number',  'group' => 'leave',      'description' => 'Days back an employee can apply for past leave'],
        'working_days_per_week'          => ['value' => '6',        'type' => 'number',  'group' => 'attendance', 'description' => 'Standard working days per week'],
    ];

    /**
     * Get setting value by key (cached).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting_{$key}", self::CACHE_TTL, function () use ($key, $default) {
            $setting = SystemSetting::where('setting_key', $key)->first();

            if (! $setting) {
                if (array_key_exists($key, self::DEFAULTS)) {
                    $def = self::DEFAULTS[$key];
                    $setting = SystemSetting::create([
                        'setting_key'   => $key,
                        'setting_value' => $def['value'],
                        'value_type'    => $def['type'],
                        'group'         => $def['group'],
                        'description'   => $def['description'],
                    ]);
                    return $setting->typed_value;
                }
                return $default;
            }

            return $setting->typed_value;
        });
    }

    /**
     * Set/update setting value by key.
     */
    public function set(string $key, mixed $value, ?int $updatedBy = null): SystemSetting
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;

        $setting = SystemSetting::where('setting_key', $key)->first();

        if ($setting) {
            $setting->update([
                'setting_value' => $stringValue,
                'updated_by'    => $updatedBy,
            ]);
        } else {
            $def = self::DEFAULTS[$key] ?? ['type' => 'string', 'group' => 'general', 'description' => ''];
            $setting = SystemSetting::create([
                'setting_key'   => $key,
                'setting_value' => $stringValue,
                'value_type'    => $def['type'],
                'group'         => $def['group'],
                'description'   => $def['description'],
                'updated_by'    => $updatedBy,
            ]);
        }

        Cache::forget("system_setting_{$key}");

        return $setting;
    }

    /**
     * Ensure all default settings rows exist in DB.
     */
    public function seedDefaults(): void
    {
        foreach (self::DEFAULTS as $key => $def) {
            $this->get($key);
        }
    }

    /**
     * Fetch all settings organized by key.
     */
    public function all(): array
    {
        $this->seedDefaults();

        return SystemSetting::with('updatedBy:id,name')
            ->orderBy('group')
            ->orderBy('setting_key')
            ->get()
            ->keyBy('setting_key')
            ->toArray();
    }
}
