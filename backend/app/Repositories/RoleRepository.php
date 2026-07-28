<?php

namespace App\Repositories;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * RoleRepository handles all database queries for roles
 */
class RoleRepository
{
    /**
     * Get all roles with permissions and user count
     *
     * @return Collection
     */
    public function getAllWithRelations(): Collection
    {
        return Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    /**
     * Find role by ID with permissions
     *
     * @param  int  $id
     * @return Role
     */
    public function findWithPermissions(int $id): Role
    {
        return Role::with('permissions:id,name')->findOrFail($id);
    }

    /**
     * Create a new role
     *
     * @param  array  $data
     * @return Role
     */
    public function create(array $data): Role
    {
        return Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);
    }

    /**
     * Update role name
     *
     * @param  Role  $role
     * @param  array  $data
     * @return Role
     */
    public function update(Role $role, array $data): Role
    {
        $role->update(['name' => $data['name']]);
        return $role->fresh();
    }

    /**
     * Delete role
     *
     * @param  Role  $role
     * @return bool
     */
    public function delete(Role $role): bool
    {
        return $role->delete();
    }

    /**
     * Sync permissions to role
     *
     * @param  Role  $role
     * @param  array  $permissions
     * @return Role
     */
    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);
        return $role->fresh(['permissions']);
    }

    /**
     * Check if role is a system role (protected)
     *
     * @param  Role  $role
     * @return bool
     */
    public function isSystemRole(Role $role): bool
    {
        return in_array($role->name, ['Admin', 'Super Admin']);
    }

    /**
     * Get role by name
     *
     * @param  string  $name
     * @return Role|null
     */
    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    /**
     * Count users assigned to role
     *
     * @param  Role  $role
     * @return int
     */
    public function countUsers(Role $role): int
    {
        return $role->users()->count();
    }
}
