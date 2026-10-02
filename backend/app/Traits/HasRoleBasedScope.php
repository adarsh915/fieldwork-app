<?php

namespace App\Traits;

use App\Models\User;
use App\Services\DataScope;
use Illuminate\Database\Eloquent\Builder;

trait HasRoleBasedScope
{
    public function scopeForUser(Builder $query, User $user, string $ownColumn = 'user_id'): Builder
    {
        $scopedIds = DataScope::visibleUserIds('day-start');
        if ($scopedIds === null) {
            return $query;
        }

        return $query->whereIn($ownColumn, $scopedIds);
    }
}
