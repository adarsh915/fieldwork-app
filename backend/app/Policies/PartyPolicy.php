<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class PartyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    public function view(User $user, Customer $party): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('customers.manage');
    }

    public function update(User $user, Customer $party): bool
    {
        return $user->hasPermissionTo('customers.edit') || $user->hasPermissionTo('customers.manage');
    }

    public function delete(User $user, Customer $party): bool
    {
        return $user->hasPermissionTo('customers.delete');
    }

    public function assign(User $user): bool
    {
        return $user->hasPermissionTo('parties.assign') || $user->hasPermissionTo('customers.manage');
    }

    public function deactivate(User $user, Customer $party): bool
    {
        return $user->hasPermissionTo('customers.manage');
    }
}
