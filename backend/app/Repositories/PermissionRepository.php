<?php

namespace App\Repositories;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

/**
 * PermissionRepository handles all database queries for permissions
 */
class PermissionRepository
{
    /**
     * Get all permissions ordered by name
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return Permission::query()->orderBy('name')->get();
    }

    /**
     * Find permission by ID
     *
     * @param  int  $id
     * @return Permission
     */
    public function findById(int $id): Permission
    {
        return Permission::findOrFail($id);
    }

    /**
     * Create a new permission
     *
     * @param  array  $data
     * @return Permission
     */
    public function create(array $data): Permission
    {
        return Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);
    }

    /**
     * Delete permission and detach from all roles
     *
     * @param  Permission  $permission
     * @return bool
     */
    public function delete(Permission $permission): bool
    {
        // Remove permission from all roles first
        $permission->roles()->detach();
        
        return $permission->delete();
    }

    /**
     * Get permissions grouped by module
     * e.g., ['users' => [...], 'employees' => [...]]
     *
     * @return array
     */
    public function getGroupedByModule(): array
    {
        $permissions = $this->getAll();
        $grouped = [];

        foreach ($permissions as $permission) {
            // Extract module from permission name (e.g., 'users.view' => 'users')
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'general';
            
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            
            $grouped[$module][] = $permission;
        }

        ksort($grouped);
        return $grouped;
    }

    /**
     * Check if permission exists by name
     *
     * @param  string  $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return Permission::where('name', $name)->exists();
    }
}
