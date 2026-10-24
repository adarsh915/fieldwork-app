<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Dealer;

/**
 * DealerPolicy handles authorization for dealer management
 */
class DealerPolicy
{
    /**
     * Determine if user can view any dealers
     * Super Admin can view all, others can view only assigned
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('dealers.view')
            || $user->hasPermissionTo('dealers.manage')
            || $user->hasRole('Super Admin');
    }

    /**
     * Determine if user can view a dealer
     * Super Admin can view all, others only assigned
     */
    public function view(User $user, Dealer $dealer): bool
    {
        if (!$user->hasPermissionTo('dealers.view') && !$user->hasPermissionTo('dealers.manage') && !$user->hasRole('Super Admin')) {
            return false;
        }

        // Super Admin can view all dealers
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Other users can only view dealers assigned to them
        return $dealer->assigned_to === $user->id;
    }

    /**
     * Determine if user can create dealers
     * Only Super Admin and users with dealers.create permission
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('dealers.create');
    }

    /**
     * Determine if user can update a dealer
     * Super Admin can update all, others only assigned
     */
    public function update(User $user, Dealer $dealer): bool
    {
        if (!$user->hasPermissionTo('dealers.edit')) {
            return false;
        }

        // Super Admin can update all dealers
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Other users can only update dealers assigned to them
        return $dealer->assigned_to === $user->id;
    }

    /**
     * Determine if user can delete a dealer
     * Only Super Admin can delete
     */
    public function delete(User $user, Dealer $dealer): bool
    {
        return $user->hasRole('Super Admin') && $user->hasPermissionTo('dealers.delete');
    }

    /**
     * Determine if user can assign dealers to users
     * Only Super Admin can assign
     */
    public function assign(User $user): bool
    {
        return $user->hasRole('Super Admin') && $user->hasPermissionTo('dealers.manage');
    }
}
