<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use App\Http\Requests\Admin\Permission\StorePermissionRequest;
use App\Services\RoleService;
use App\Services\PermissionService;

/**
 * RoleController handles role and permission management for Super Admin
 * 
 * THIN CONTROLLER - All business logic in RoleService and PermissionService
 * All database queries in RoleRepository and PermissionRepository
 */
class RoleController extends Controller
{
    protected RoleService $roleService;
    protected PermissionService $permissionService;

    public function __construct(
        RoleService $roleService,
        PermissionService $permissionService
    ) {
        $this->roleService = $roleService;
        $this->permissionService = $permissionService;
    }

    /**
     * Display roles and permissions
     * Only Super Admin can access
     */
    public function index()
    {
        // Authorization check
        $this->authorize('viewAny', \Spatie\Permission\Models\Role::class);

        // Get data from services
        $roles = $this->roleService->getAllRoles();
        $permissions = $this->permissionService->getAllPermissions();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    /**
     * Show create role form
     */
    public function create()
    {
        // Authorization check
        $this->authorize('create', \Spatie\Permission\Models\Role::class);

        $permissions = $this->permissionService->getAllPermissions();
        
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store new role
     */
    public function store(StoreRoleRequest $request)
    {
        // Authorization handled by FormRequest
        // Validation handled by FormRequest
        
        $role = $this->roleService->createRole($request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role "' . $role->name . '" created successfully!');
    }

    /**
     * Show edit role form
     */
    public function edit($id)
    {
        // Get role with permissions
        $role = $this->roleService->getRoleById($id);
        
        // Authorization check
        $this->authorize('update', $role);

        $permissions = $this->permissionService->getAllPermissions();
        
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update role
     */
    public function update(UpdateRoleRequest $request, $id)
    {
        // Authorization handled by FormRequest
        // Validation handled by FormRequest
        
        $role = $this->roleService->updateRole($id, $request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role "' . $role->name . '" updated successfully!');
    }

    /**
     * Delete role
     */
    public function destroy($id)
    {
        // Get role first
        $role = $this->roleService->getRoleById($id);
        
        // Authorization check
        $this->authorize('delete', $role);

        // Check if role can be deleted
        $canDelete = $this->roleService->canDeleteRole($id);
        
        if (!$canDelete['can_delete']) {
            return back()->with('error', $canDelete['reason']);
        }

        // Delete role
        $this->roleService->deleteRole($id);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully!');
    }

    /**
     * Show create permission form
     */
    public function createPermission()
    {
        // Authorization check
        $this->authorize('create', \Spatie\Permission\Models\Permission::class);

        return view('admin.roles.create-permission');
    }

    /**
     * Store new permission
     */
    public function storePermission(StorePermissionRequest $request)
    {
        // Authorization handled by FormRequest
        // Validation handled by FormRequest
        
        $permission = $this->permissionService->createPermission($request->validated());

        return redirect()->route('admin.roles.index')
            ->with('success', 'Permission "' . $permission->name . '" created successfully!');
    }

    /**
     * Delete permission
     */
    public function destroyPermission($id)
    {
        // Authorization check
        $this->authorize('delete', \Spatie\Permission\Models\Permission::class);

        $this->permissionService->deletePermission($id);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Permission deleted successfully!');
    }
}
