<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * PermissionPolicy handles authorization for permission management
 * Only Super Admin can manage permissions
 */
class PermissionPolicy
{
    /**
     * Determine if user can view any permissions
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine if user can view a permission
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine if user can create permissions
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine if user can delete a permission
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasRole('Super Admin');
    }
}
