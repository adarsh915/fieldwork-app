<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * API Authentication Controller
 *
 * Handles mobile/API login, logout, and profile retrieval.
 * All routes are prefixed with /api (see routes/api.php).
 */
class AuthController extends Controller
{
    /**
     * Login with email and password.
     *
     * POST /api/auth/login
     * Body: { email, password }
     *
     * Returns: { user, token }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Please check your email and password.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact admin.',
            ], 403);
        }

        // Revoke all previous tokens for this device (optional: keep for multi-device)
        // $user->tokens()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'token' => $token,
                'user'  => $this->formatUser($user),
            ],
        ], 200);
    }

    /**
     * Get authenticated user profile.
     *
     * GET /api/auth/me
     * Header: Authorization: Bearer {token}
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'user' => $this->formatUser($request->user()),
            ],
        ]);
    }

    /**
     * Logout (revoke current token).
     *
     * POST /api/auth/logout
     * Header: Authorization: Bearer {token}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Format the user object for API responses.
     * Keeps the response consistent across all auth endpoints.
     */
    private function formatUser(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'employee_id' => $user->employee_id,
            'department'  => $user->department,
            'position'    => $user->position,
            'is_active'   => $user->is_active,
            'roles'       => $user->getRoleNames(),        // from spatie/laravel-permission
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }
}
