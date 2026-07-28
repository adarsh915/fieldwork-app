<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use App\Repositories\PermissionRepository;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * RoleService handles all business logic for role management
 */
class RoleService
{
    protected RoleRepository $roleRepository;
    protected PermissionRepository $permissionRepository;

    public function __construct(
        RoleRepository $roleRepository,
        PermissionRepository $permissionRepository
    ) {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Get all roles with their permissions and user counts
     *
     * @return Collection
     */
    public function getAllRoles(): Collection
    {
        return $this->roleRepository->getAllWithRelations();
    }

    /**
     * Get role by ID with permissions
     *
     * @param  int  $id
     * @return Role
     */
    public function getRoleById(int $id): Role
    {
        return $this->roleRepository->findWithPermissions($id);
    }

    /**
     * Create a new role with permissions
     *
     * @param  array  $data
     * @return Role
     * @throws \Exception
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            // Create the role
            $role = $this->roleRepository->create($data);

            // Assign permissions if provided
            if (!empty($data['permissions'])) {
                $role = $this->roleRepository->syncPermissions($role, $data['permissions']);
            }

            // Clear permission cache
            $this->clearPermissionCache();

            return $role;
        });
    }

    /**
     * Update role name and permissions
     *
     * @param  int  $id
     * @param  array  $data
     * @return Role
     * @throws \Exception
     */
    public function updateRole(int $id, array $data): Role
    {
        return DB::transaction(function () use ($id, $data) {
            $role = $this->roleRepository->findWithPermissions($id);

            // Update role name
            $role = $this->roleRepository->update($role, $data);

            // Sync permissions
            $permissions = $data['permissions'] ?? [];
            $role = $this->roleRepository->syncPermissions($role, $permissions);

            // Clear permission cache
            $this->clearPermissionCache();

            return $role;
        });
    }

    /**
     * Delete role
     *
     * @param  int  $id
     * @return bool
     * @throws \Exception
     */
    public function deleteRole(int $id): bool
    {
        $role = $this->roleRepository->findWithPermissions($id);

        // Prevent deleting system roles
        if ($this->roleRepository->isSystemRole($role)) {
            throw new \Exception('Cannot delete system role: ' . $role->name);
        }

        // Delete the role
        $deleted = $this->roleRepository->delete($role);

        if ($deleted) {
            // Clear permission cache
            $this->clearPermissionCache();
        }

        return $deleted;
    }

    /**
     * Check if role can be deleted
     *
     * @param  int  $id
     * @return array ['can_delete' => bool, 'reason' => string|null]
     */
    public function canDeleteRole(int $id): array
    {
        $role = $this->roleRepository->findWithPermissions($id);

        // Check if system role
        if ($this->roleRepository->isSystemRole($role)) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete system role: ' . $role->name
            ];
        }

        // Check if role has users
        $userCount = $this->roleRepository->countUsers($role);
        if ($userCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Role has {$userCount} user(s) assigned. Please reassign users first."
            ];
        }

        return ['can_delete' => true, 'reason' => null];
    }

    /**
     * Clear Spatie permission cache
     *
     * @return void
     */
    protected function clearPermissionCache(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
