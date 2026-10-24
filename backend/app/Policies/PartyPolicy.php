<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Services\DataScope;

class PartyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin'])
            || $user->hasAnyPermission([
                'customers.view', 'customers.manage',
                'parties.view', 'parties.view-team', 'parties.view-all', 'parties.view-own'
            ]);
    }

    public function view(User $user, Customer $party): bool
    {
        if (!$this->viewAny($user)) {
            return false;
        }

        $visibleIds = DataScope::visibleUserIds('parties');
        if ($visibleIds === null) {
            return true;
        }

        return $party->assignments()
            ->whereIn('employee_id', $visibleIds)
            ->where('is_active', true)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('customers.create')
            || $user->hasPermissionTo('customers.manage')
            || $user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin']);
    }

    public function update(User $user, Customer $party): bool
    {
        if (!$user->hasPermissionTo('customers.edit')
            && !$user->hasPermissionTo('customers.manage')
            && !$user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin'])) {
            return false;
        }

        $visibleIds = DataScope::visibleUserIds('parties');
        if ($visibleIds === null) {
            return true;
        }

        return $party->assignments()
            ->whereIn('employee_id', $visibleIds)
            ->where('is_active', true)
            ->exists();
    }

    public function delete(User $user, Customer $party): bool
    {
        if (!$user->hasPermissionTo('customers.delete')
            && !$user->hasPermissionTo('customers.manage')
            && !$user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin'])) {
            return false;
        }

        $visibleIds = DataScope::visibleUserIds('parties');
        if ($visibleIds === null) {
            return true;
        }

        return $party->assignments()
            ->whereIn('employee_id', $visibleIds)
            ->where('is_active', true)
            ->exists();
    }

    public function assign(User $user): bool
    {
        return $user->hasPermissionTo('parties.assign')
            || $user->hasPermissionTo('customers.manage')
            || $user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin']);
    }

    public function deactivate(User $user, Customer $party): bool
    {
        return $user->hasPermissionTo('customers.manage')
            || $user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin']);
    }
}
