<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * GET /api/admin/settings
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success'  => true,
            'settings' => $this->settings->all(),
        ]);
    }

    /**
     * PATCH /api/admin/settings/{key}
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => 'required',
        ]);

        $setting = $this->settings->set(
            $key,
            $request->input('value'),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => "Setting '{$key}' updated successfully.",
            'data'    => $setting,
        ]);
    }
}
