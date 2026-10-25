<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * DataScope — centralizes who the logged-in user is allowed to see and manage.
 *
 * Reads permission-based scope flags so any role can be elevated
 * without code changes (just grant the right permission).
 *
 * Scope levels:
 *   view-all   → unrestricted (null) → see everyone
 *   view-team  → subtree strictly under me (subordinates)
 *   view-own   → only myself
 */
class DataScope
{
    /**
     * Map module names to all possible permission name synonyms (singular, plural, alternative terms).
     */
    protected static array $aliasGroups = [
        'leave'         => ['leave', 'leaves'],
        'leaves'        => ['leave', 'leaves'],
        'customer'      => ['parties', 'party', 'customers', 'customer'],
        'customers'     => ['parties', 'party', 'customers', 'customer'],
        'party'         => ['parties', 'party', 'customers', 'customer'],
        'parties'       => ['parties', 'party', 'customers', 'customer'],
        'employee'      => ['employee', 'employees'],
        'employees'     => ['employee', 'employees'],
        'expense'       => ['expense', 'expenses'],
        'expenses'      => ['expense', 'expenses'],
        'order'         => ['order', 'orders'],
        'orders'        => ['order', 'orders'],
        'payment'       => ['payment', 'payments'],
        'payments'      => ['payment', 'payments'],
        'visit'         => ['visit', 'visits'],
        'visits'        => ['visit', 'visits'],
        'day-start'     => ['day_start', 'day-start', 'day_starts', 'day-starts'],
        'day_start'     => ['day_start', 'day-start', 'day_starts', 'day-starts'],
        'day-starts'    => ['day_start', 'day-start', 'day_starts', 'day-starts'],
        'day_starts'    => ['day_start', 'day-start', 'day_starts', 'day-starts'],
        'attendance'    => ['attendance', 'attendances'],
        'attendances'   => ['attendance', 'attendances'],
        'transaction'   => ['transaction', 'transactions'],
        'transactions'  => ['transaction', 'transactions'],
        'report'        => ['report', 'reports'],
        'reports'       => ['report', 'reports'],
        'dealer'        => ['dealer', 'dealers'],
        'dealers'       => ['dealer', 'dealers'],
        'deal'          => ['deal', 'deals'],
        'deals'         => ['deal', 'deals'],
        'meeting'       => ['meeting', 'meetings'],
        'meetings'      => ['meeting', 'meetings'],
        'product'       => ['product', 'products'],
        'products'      => ['product', 'products'],
        'product-demo'  => ['product-demo', 'product_demo'],
        'product_demo'  => ['product-demo', 'product_demo'],
        'activity'      => ['activity', 'activities'],
        'activities'    => ['activity', 'activities'],
        'fuel-rate'     => ['fuel-rate', 'fuel-rates', 'fuel_rate', 'fuel_rates'],
        'fuel-rates'    => ['fuel-rate', 'fuel-rates', 'fuel_rate', 'fuel_rates'],
    ];

    /**
     * Determines whether the currently authenticated user has ANY view permission for a module
     * (view-all, view-team, view-own, view, manage, or approve).
     */
    public static function canView(string $module, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;

        // Super Admin / Admin bypass
        if ($user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin'])) {
            return true;
        }

        $modulesToCheck = static::$aliasGroups[$module] ?? [$module];
        $suffixes = ['view', 'view-all', 'view-team', 'view-own', 'manage', 'approve', 'final_approve'];

        // Build list of target permission names
        $targets = [];
        foreach ($modulesToCheck as $m) {
            foreach ($suffixes as $s) {
                $targets[] = "{$m}.{$s}";
            }
        }

        // 1. Standard check
        foreach ($targets as $target) {
            if ($user->can($target)) {
                return true;
            }
        }

        // 2. Direct database permission name lookup across user roles (immune to guard mismatch or cache)
        try {
            $userPermNames = $user->getAllPermissions()->pluck('name')->toArray();
            foreach ($targets as $target) {
                if (in_array($target, $userPermNames, true)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Ignore if relations not available
        }

        return false;
    }

    /**
     * Returns array of user IDs the logged-in user may see, or null if unrestricted (view all).
     *
     * Rules:
     *   1. view-all (or Super Admin/Admin): unrestricted -> returns null (all users).
     *   2. view-team (without view-own): returns ONLY subordinates below the user in hierarchy.
     *   3. view-team AND view-own: returns subordinates + the user themselves.
     *   4. view-own ONLY: returns ONLY the user themselves.
     *   5. Default fallback (if neither is explicitly granted): returns ONLY the user themselves.
     *
     * @param  string $module  e.g. 'visits', 'day-start', 'parties', 'expenses', 'reports', 'leaves'
     * @return array|null     null = unrestricted / see ALL, array = scoped user IDs
     */
    public static function visibleUserIds(string $module = 'visits', ?User $user = null): ?array
    {
        $user = $user ?? auth('sanctum')->user() ?? auth()->user() ?? request()?->user();
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

        // 2. Check for view-team and view-own
        $hasTeam = false;
        $hasOwn = false;
        foreach ($modulesToCheck as $m) {
            if ($user->can("{$m}.view-team")) {
                $hasTeam = true;
            }
            if ($user->can("{$m}.view-own")) {
                $hasOwn = true;
            }
        }

        $ids = [];

        // If user has team permission, include subordinates strictly below them
        if ($hasTeam) {
            $ids = array_merge($ids, static::subtreeIds($user->id));
        }

        // If user has own permission, include the user themselves
        if ($hasOwn) {
            $ids[] = $user->id;
        }

        // Default fallback: if neither view-all, view-team, nor view-own is set, default to own only
        if (!$hasTeam && !$hasOwn) {
            $ids[] = $user->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Returns array of user IDs the logged-in user is authorized to ACTION (Approve, Reject, or Edit).
     * STRICT HIERARCHY RULE: Subordinates ONLY. A user can NEVER approve, reject, or administrative-edit themselves.
     *
     * @param  string $module
     * @return array|null  null = Super Admin/unrestricted, array = allowed subordinate IDs
     */
    public static function actionableSubordinateIds(string $module = 'expenses', ?User $user = null): ?array
    {
        $user = $user ?? auth('sanctum')->user() ?? auth()->user() ?? request()?->user();
        if (!$user) return [];

        // Super Admin bypass — top authority can action all
        if ($user->hasRole(['Super Admin', 'super_admin'])) {
            return null;
        }

        $modulesToCheck = static::$aliasGroups[$module] ?? [$module];

        // Check if user has view-all or is top Admin
        $hasAll = false;
        if ($user->hasRole(['Admin', 'admin'])) {
            $hasAll = true;
        } else {
            // ONLY explicit view-all grants company-wide action scope (e.g., HR/Finance for expenses)
            // .manage permission grants management of their OWN team, NOT their superiors!
            foreach ($modulesToCheck as $m) {
                if ($user->can("{$m}.view-all")) {
                    $hasAll = true;
                    break;
                }
            }
        }

        // Managers and team leaders must ONLY action their team subordinates (never superiors or peers)
        if ($hasAll && !$user->hasRole(['Manager', 'manager'])) {
            // Can action all employees EXCEPT themselves (preventing self-approval)
            return User::where('id', '!=', $user->id)->pluck('id')->toArray();
        }

        // For Team / Managers: returns ONLY subordinates strictly below this user in the reporting_to tree
        return static::subtreeIds($user->id);
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

        // Has parties.assign → can assign to their visible scoped users
        if ($user->can('parties.assign') || $user->can('customers.manage')) {
            $ids = static::visibleUserIds('parties');
            return User::whereIn('id', $ids ?? [])->where('is_active', true);
        }

        // No assign permission → empty (can't assign anyone)
        return User::whereRaw('1=0');
    }

    /**
     * Recursively gets all user IDs strictly below a given user in reporting_to.
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

    /**
     * Checks if an employee is a direct report of a manager (reporting_to == managerId).
     */
    public static function isDirectReport(int $managerId, int $employeeId): bool
    {
        return User::where('id', $employeeId)->where('reporting_to', $managerId)->exists();
    }

    /**
     * Returns array of user IDs who report directly to a given manager.
     */
    public static function directReportIds(int $managerId): array
    {
        return User::where('reporting_to', $managerId)->pluck('id')->toArray();
    }
}
