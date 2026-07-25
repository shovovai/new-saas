<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * @var list<string>
     */
    private const KEYS = ['site_name', 'support_email', 'maintenance_mode', 'maintenance_message'];

    public function index(): Response
    {
        $settings = collect(self::KEYS)->mapWithKeys(fn (string $key) => [$key => Setting::get($key)]);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'support_email' => ['required', 'email', 'max:255'],
            'maintenance_mode' => ['required', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : $value);
        }

        return back()->with('success', 'Settings updated.');
    }
}
