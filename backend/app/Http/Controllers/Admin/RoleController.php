<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use App\Http\Requests\Admin\Permission\StorePermissionRequest;
use App\Services\RoleService;
use App\Services\PermissionService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
        $this->authorize('viewAny', Role::class);

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
        $this->authorize('create', Role::class);

        $permissions = $this->permissionService->getAllPermissions();
        
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store new role
     */
    public function store(StoreRoleRequest $request)
    {
        $role = $this->roleService->createRole($request->validated());

        \App\Models\AuditLog::logEvent(
            event: 'role.created',
            auditable: $role,
            oldValues: null,
            newValues: ['name' => $role->name, 'permissions' => $request->input('permissions', [])],
            reason: 'Role created by ' . (auth()->user()?->name ?? 'Admin')
        );

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
        $oldRole = $this->roleService->getRoleById($id);
        $oldPerms = $oldRole->permissions->pluck('name')->toArray();

        $role = $this->roleService->updateRole($id, $request->validated());
        $newPerms = $role->fresh()->permissions->pluck('name')->toArray();

        \App\Models\AuditLog::logEvent(
            event: 'role.updated',
            auditable: $role,
            oldValues: ['name' => $oldRole->name, 'permissions' => $oldPerms],
            newValues: ['name' => $role->name, 'permissions' => $newPerms],
            reason: 'Role updated by ' . (auth()->user()?->name ?? 'Admin')
        );

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

        $roleName = $role->name;

        // Delete role
        $this->roleService->deleteRole($id);

        \App\Models\AuditLog::logEvent(
            event: 'role.deleted',
            auditable: $role,
            oldValues: ['name' => $roleName],
            newValues: null,
            reason: 'Role deleted by ' . (auth()->user()?->name ?? 'Admin')
        );

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully!');
    }

    /**
     * Show create permission form
     */
    public function createPermission()
    {
        // Authorization check
        $this->authorize('create', Permission::class);

        return view('admin.roles.create-permission');
    }

    /**
     * Store new permission
     */
    public function storePermission(StorePermissionRequest $request)
    {
        $permission = $this->permissionService->createPermission($request->validated());

        \App\Models\AuditLog::logEvent(
            event: 'permission.created',
            auditable: $permission,
            oldValues: null,
            newValues: ['name' => $permission->name],
            reason: 'Permission created by ' . (auth()->user()?->name ?? 'Admin')
        );

        return redirect()->route('admin.roles.index')
            ->with('success', 'Permission "' . $permission->name . '" created successfully!');
    }

    /**
     * Delete permission
     */
    public function destroyPermission($id)
    {
        // Authorization check
        $this->authorize('delete', Permission::class);

        $permission = Permission::find($id);
        $name = $permission?->name ?? 'Unknown';

        $this->permissionService->deletePermission($id);

        \App\Models\AuditLog::logEvent(
            event: 'permission.deleted',
            auditable: $permission,
            oldValues: ['name' => $name],
            newValues: null,
            reason: 'Permission deleted by ' . (auth()->user()?->name ?? 'Admin')
        );

        return redirect()->route('admin.roles.index')
            ->with('success', 'Permission deleted successfully!');
    }
}
