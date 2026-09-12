<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationManagementController extends Controller
{
    public function index(): Response
    {
        $applications = Application::withCount('users')
            ->latest()
            ->get();

        return Inertia::render('admin/Applications/Index', [
            'applications' => $applications,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:applications,slug',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'base_url' => 'required|url|max:255',
            'callback_url' => 'required|url|max:255',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        Application::create($validated);

        return back()->with('success', 'Aplikasi berhasil ditambahkan.');
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:applications,slug,' . $application->id,
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'base_url' => 'required|url|max:255',
            'callback_url' => 'required|url|max:255',
            'is_active' => 'boolean',
        ]);

        $application->update($validated);

        return back()->with('success', 'Aplikasi berhasil diperbarui.');
    }

    public function destroy(Application $application): RedirectResponse
    {
        $application->delete();

        return back()->with('success', 'Aplikasi berhasil dihapus.');
    }

    public function regenerateSecret(Application $application): RedirectResponse
    {
        $application->update([
            'client_secret' => Str::random(48),
        ]);

        return back()->with('success', 'Client Secret berhasil digenerate ulang.');
    }
}
