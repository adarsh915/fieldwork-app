<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingWebController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index()
    {
        $settings = $this->settings->all();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $inputSettings = $request->except(['_token', '_method']);

        foreach ($inputSettings as $key => $value) {
            $this->settings->set($key, $value, auth()->id());
        }

        return back()->with('success', 'System settings updated successfully.');
    }
}
