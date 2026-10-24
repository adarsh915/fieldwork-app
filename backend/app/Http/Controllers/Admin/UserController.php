<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $search = request('q');

        return view('admin.users.index', [
            'users' => User::query()
                ->with('roles')
                ->when($search, function ($query, $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('id', 'desc')
                ->paginate(20)
                ->withQueryString(),
            'roles' => Role::query()->orderBy('name')->get(),
            'search' => $search,
        ]);
    }

    public function updateRoles(Request $request, User $user)
    {
        $currentUser = $request->user();
        if (!$currentUser || (!$currentUser->can('employees.assign-roles') && !$currentUser->hasRole('Super Admin'))) {
            abort(403, 'You do not have permission to assign or modify roles.');
        }

        // Prevent non-Super Admin from modifying a Super Admin's roles
        if ($user->hasRole('Super Admin') && !$currentUser->hasRole('Super Admin')) {
            abort(403, 'Only Super Admins can modify Super Admin accounts.');
        }

        $data = $request->validate([
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'roles' => ['nullable'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $roleToSync = [];
        if (!empty($data['role'])) {
            $roleToSync = [$data['role']];
        } elseif (!empty($data['roles'])) {
            $rolesArr = is_array($data['roles']) ? $data['roles'] : [$data['roles']];
            $roleToSync = array_slice(array_filter($rolesArr), 0, 1);
        }

        // Prevent non-Super Admin from granting the Super Admin role
        if (in_array('Super Admin', $roleToSync) && !$currentUser->hasRole('Super Admin')) {
            abort(403, 'Only Super Admins can grant the Super Admin role.');
        }

        $oldRoles = $user->roles()->pluck('name')->toArray();
        $user->syncRoles($roleToSync);
        $newRoles = $user->fresh()->roles()->pluck('name')->toArray();

        \App\Models\AuditLog::logEvent(
            event: 'user.roles_updated',
            auditable: $user,
            oldValues: ['roles' => $oldRoles],
            newValues: ['roles' => $newRoles],
            reason: 'User roles updated by ' . (auth()->user()?->name ?? 'Admin')
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Role for {$user->name} updated successfully.",
                'roles' => $newRoles,
            ]);
        }

        return back()->with('status', 'User role updated.');
    }
}
