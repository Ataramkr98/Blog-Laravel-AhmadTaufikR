<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(): View
    {
        $settings = SystemSetting::current();

        return view('admin.settings', [
            'settings' => [
                'maintenanceMode' => $settings->maintenance_mode,
                'reviewNotifications' => $settings->review_notifications,
                'glossaryCacheMinutes' => $settings->glossary_cache_minutes,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'maintenanceMode' => 'required|in:0,1',
            'reviewNotifications' => 'required|in:0,1',
            'glossaryCacheMinutes' => 'required|integer|min:1|max:1440',
        ]);

        $settings = SystemSetting::current();
        $settings->update([
            'maintenance_mode' => (bool) $validated['maintenanceMode'],
            'review_notifications' => (bool) $validated['reviewNotifications'],
            'glossary_cache_minutes' => (int) $validated['glossaryCacheMinutes'],
        ]);

        return back()->with('success', 'Pengaturan sistem berhasil diperbarui!');
    }
}
