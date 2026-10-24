<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * RolePolicy handles authorization for role management
 * Only Super Admin can manage roles
 */
class RolePolicy
{
    /**
     * Determine if user can view any roles
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasPermissionTo('roles.view')
            || $user->hasPermissionTo('roles.manage');
    }

    /**
     * Determine if user can view a role
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasPermissionTo('roles.view')
            || $user->hasPermissionTo('roles.manage');
    }

    /**
     * Determine if user can create roles
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasPermissionTo('roles.create')
            || $user->hasPermissionTo('roles.manage');
    }

    /**
     * Determine if user can update a role
     */
    public function update(User $user, Role $role): bool
    {
        // Only Super Admin can edit Super Admin or Admin roles
        if (in_array($role->name, ['Super Admin', 'Admin']) && !$user->hasRole('Super Admin')) {
            return false;
        }

        return $user->hasRole('Super Admin')
            || $user->hasPermissionTo('roles.edit')
            || $user->hasPermissionTo('roles.manage');
    }

    /**
     * Determine if user can delete a role
     */
    public function delete(User $user, Role $role): bool
    {
        // Cannot delete system roles
        if (in_array($role->name, ['Admin', 'Super Admin'])) {
            return false;
        }

        return $user->hasRole('Super Admin')
            || $user->hasPermissionTo('roles.delete')
            || $user->hasPermissionTo('roles.manage');
    }
}
