<?php

namespace App\Policies;

use App\Models\User;
use App\Services\DataScope;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employees.view')
            || $user->hasPermissionTo('users.view')
            || $user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if (!$this->viewAny($user)) {
            return false;
        }

        $visibleIds = DataScope::visibleUserIds('employees');
        if ($visibleIds === null) {
            return true;
        }

        return in_array($model->id, $visibleIds, true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employees.create')
            || $user->hasPermissionTo('users.manage')
            || $user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('employees.edit')
            && !$user->hasPermissionTo('users.manage')
            && !$user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin'])) {
            return false;
        }

        $visibleIds = DataScope::visibleUserIds('employees');
        if ($visibleIds === null) {
            return true;
        }

        return in_array($model->id, $visibleIds, true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false; // Cannot delete self
        }

        if (!$user->hasPermissionTo('employees.delete')
            && !$user->hasPermissionTo('users.manage')
            && !$user->hasRole(['Super Admin', 'Admin', 'admin', 'super_admin'])) {
            return false;
        }

        $visibleIds = DataScope::visibleUserIds('employees');
        if ($visibleIds === null) {
            return true;
        }

        return in_array($model->id, $visibleIds, true);
    }
}
