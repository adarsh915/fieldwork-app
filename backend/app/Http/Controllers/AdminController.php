<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Admin dashboard data placeholder',
            'metrics' => [
                'active_users' => User::count(),
                'recent_logins' => 0,
            ],
        ]);
    }

    public function users()
    {
        $users = User::select('id', 'name', 'email', 'created_at')->paginate(20);

        return response()->json($users);
    }
}
