<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingWebController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(Request $request)
    {
        $settings = $this->settings->all();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'settings' => $settings,
            ]);
        }

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $inputSettings = $request->except(['_token', '_method']);

        foreach ($inputSettings as $key => $value) {
            $this->settings->set($key, $value, auth()->id());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'System settings saved and applied successfully.',
                'settings' => $this->settings->all(),
            ]);
        }

        return back()->with('success', 'System settings updated successfully.');
    }
}
