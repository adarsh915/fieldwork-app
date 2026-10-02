<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * DataScope — centralizes who the logged-in user is allowed to see.
 *
 * Reads permission-based scope flags so any role can be elevated
 * without code changes (just grant the right permission).
 *
 * Scope levels (checked in order):
 *   view-all   → no WHERE filter  → see everyone
 *   view-team  → subtree under me (direct reports + their reports)
 *   view-own   → only myself
 */
class DataScope
{
    /**
     * Map aliases so permission checks work whether module is singular or plural.
     */
    protected static array $aliases = [
        'leave'       => 'leaves',
        'leaves'      => 'leave',
        'customer'    => 'parties',
        'customers'   => 'parties',
        'party'       => 'parties',
        'parties'     => 'party',
        'employee'    => 'employees',
        'employees'   => 'employee',
        'expense'     => 'expenses',
        'expenses'    => 'expense',
        'order'       => 'orders',
        'orders'      => 'order',
        'payment'     => 'payments',
        'payments'    => 'payment',
        'visit'       => 'visits',
        'visits'      => 'visit',
        'day-start'   => 'day_start',
        'day_start'   => 'day-start',
        'transaction' => 'transactions',
        'transactions'=> 'transaction',
    ];

    /**
     * Returns array of user IDs the logged-in user may see, or null if unrestricted (view all).
     *
     * @param  string $module  e.g. 'visits', 'day-start', 'parties', 'expenses'
     * @return array|null     null = unrestricted / see ALL, array = scoped user IDs
     */
    public static function visibleUserIds(string $module = 'visits'): ?array
    {
        $user = auth()->user();
        if (!$user) return [];

        // Super Admin / Admin bypass — always see all
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return null;
        }

        $alias = static::$aliases[$module] ?? null;

        // view-all permission → unrestricted (null)
        if ($user->can("{$module}.view-all") || ($alias && $user->can("{$alias}.view-all"))) {
            return null;
        }

        // view-team permission → full subtree under me
        if ($user->can("{$module}.view-team") || ($alias && $user->can("{$alias}.view-team"))) {
            $ids = static::subtreeIds($user->id);
            return array_values(array_unique(array_merge([$user->id], $ids)));
        }

        // view-own or default → only self
        return [$user->id];
    }

    /**
     * Applies data scoping to a query builder directly.
     */
    public static function apply(Builder $query, string $module = 'visits', string $column = 'user_id'): Builder
    {
        $scopedIds = static::visibleUserIds($module);
        if ($scopedIds !== null) {
            $query->whereIn($column, $scopedIds);
        }
        return $query;
    }

    /**
     * Returns a scoped User query for employee assignment dropdowns.
     * Only returns users the logged-in user is allowed to assign parties to.
     */
    public static function assignableUsers(): Builder
    {
        $user = auth()->user();
        if (!$user) return User::whereRaw('1=0');

        // Admin / Super Admin → all active field workers
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return User::where('is_active', true);
        }

        // Has parties.assign → can assign to themselves and their subtree (FWs under them)
        if ($user->can('parties.assign') || $user->can('customers.manage')) {
            $ids = static::subtreeIds($user->id);
            $allIds = array_unique(array_merge([$user->id], $ids));
            return User::whereIn('id', $allIds)->where('is_active', true);
        }

        // No assign permission → empty (can't assign anyone)
        return User::whereRaw('1=0');
    }

    /**
     * Recursively gets all user IDs under a given user.
     */
    public static function subtreeIds(int $userId): array
    {
        $all = [];
        $currentLevel = User::where('reporting_to', $userId)->pluck('id')->toArray();

        while (!empty($currentLevel)) {
            $all = array_merge($all, $currentLevel);
            $currentLevel = User::whereIn('reporting_to', $currentLevel)->pluck('id')->toArray();
        }

        return array_values(array_unique($all));
    }
}
