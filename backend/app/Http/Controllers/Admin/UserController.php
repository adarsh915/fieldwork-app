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
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string'],
        ]);

        $user->syncRoles($data['roles'] ?? []);

        return back()->with('status', 'User roles updated.');
    }
}
