<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasRoleBasedScope
{
    public function scopeForUser(Builder $query, User $user, string $ownColumn = 'user_id'): Builder
    {
        if ($user->hasAnyRole(['admin', 'Admin', 'Super Admin'])) {
            return $query;
        }

        if ($user->hasAnyRole(['manager', 'Manager'])) {
            $teamMemberIds = $user->teamMembers()->pluck('id')->push($user->id)->all();

            return $query->whereIn($ownColumn, $teamMemberIds);
        }

        return $query->where($ownColumn, $user->id);
    }
}
