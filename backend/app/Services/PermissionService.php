<?php

namespace App\Services;

use App\Repositories\PermissionRepository;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

/**
 * PermissionService handles all business logic for permission management
 */
class PermissionService
{
    protected PermissionRepository $permissionRepository;

    public function __construct(PermissionRepository $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Get all permissions ordered by name
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        return $this->permissionRepository->getAll();
    }

    /**
     * Get permissions grouped by module
     *
     * @return array
     */
    public function getPermissionsGroupedByModule(): array
    {
        return $this->permissionRepository->getGroupedByModule();
    }

    /**
     * Create a new permission
     *
     * @param  array  $data
     * @return Permission
     */
    public function createPermission(array $data): Permission
    {
        $permission = $this->permissionRepository->create($data);

        // Clear permission cache
        $this->clearPermissionCache();

        return $permission;
    }

    /**
     * Delete permission
     *
     * @param  int  $id
     * @return bool
     */
    public function deletePermission(int $id): bool
    {
        $permission = $this->permissionRepository->findById($id);

        $deleted = $this->permissionRepository->delete($permission);

        if ($deleted) {
            // Clear permission cache
            $this->clearPermissionCache();
        }

        return $deleted;
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
