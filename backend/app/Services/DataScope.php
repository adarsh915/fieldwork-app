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
     * Map module names to all possible permission name synonyms (singular, plural, alternative terms).
     */
    protected static array $aliasGroups = [
        'leave'       => ['leave', 'leaves'],
        'leaves'      => ['leave', 'leaves'],
        'customer'    => ['parties', 'party', 'customers', 'customer'],
        'customers'   => ['parties', 'party', 'customers', 'customer'],
        'party'       => ['parties', 'party', 'customers', 'customer'],
        'parties'     => ['parties', 'party', 'customers', 'customer'],
        'employee'    => ['employee', 'employees'],
        'employees'   => ['employee', 'employees'],
        'expense'     => ['expense', 'expenses'],
        'expenses'    => ['expense', 'expenses'],
        'order'       => ['order', 'orders'],
        'orders'      => ['order', 'orders'],
        'payment'     => ['payment', 'payments'],
        'payments'    => ['payment', 'payments'],
        'visit'       => ['visit', 'visits'],
        'visits'      => ['visit', 'visits'],
        'day-start'   => ['day_start', 'day-start'],
        'day_start'   => ['day_start', 'day-start'],
        'transaction' => ['transaction', 'transactions'],
        'transactions'=> ['transaction', 'transactions'],
        'report'      => ['report', 'reports'],
        'reports'     => ['report', 'reports'],
    ];

    /**
     * Returns array of user IDs the logged-in user may see, or null if unrestricted (view all).
     *
     * @param  string $module  e.g. 'visits', 'day-start', 'parties', 'expenses', 'reports'
     * @return array|null     null = unrestricted / see ALL, array = scoped user IDs
     */
    public static function visibleUserIds(string $module = 'visits'): ?array
    {
        $user = auth()->user();
        if (!$user) return [];

        // Super Admin / Admin bypass — always see all
        if ($user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin'])) {
            return null;
        }

        $modulesToCheck = static::$aliasGroups[$module] ?? [$module];

        // 1. Explicit view-all permission → unrestricted (null = see everyone)
        foreach ($modulesToCheck as $m) {
            if ($user->can("{$m}.view-all")) {
                return null;
            }
        }

        // 2. Explicit view-team permission → full subtree under me (self + all subordinates)
        foreach ($modulesToCheck as $m) {
            if ($user->can("{$m}.view-team")) {
                $ids = static::subtreeIds($user->id);
                return array_values(array_unique(array_merge([$user->id], $ids)));
            }
        }

        // 3. Explicit view-own or default fallback → only self
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
     * Applies party data scoping: strictly restricts to actively assigned parties.
     */
    public static function applyParties(Builder $query): Builder
    {
        $visibleIds = static::visibleUserIds('parties');
        if ($visibleIds !== null) {
            $query->whereHas('assignments', function ($sq) use ($visibleIds) {
                $sq->whereIn('employee_id', $visibleIds)->where('is_active', true);
            });
        } else {
            $query->whereHas('assignments', function ($sq) {
                $sq->where('is_active', true);
            });
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
