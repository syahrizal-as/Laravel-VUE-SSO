<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserAccessManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $users = User::with(['applications:id,name,slug,icon'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $applications = Application::where('is_active', true)
            ->select('id', 'name', 'slug', 'icon')
            ->get();

        return Inertia::render('admin/Users/Index', [
            'users' => $users,
            'applications' => $applications,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function updateAccess(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'application_ids' => 'present|array',
            'application_ids.*' => 'exists:applications,id',
            'is_admin' => 'boolean',
        ]);

        if (isset($validated['is_admin'])) {
            $user->update(['is_admin' => $validated['is_admin']]);
        }

        // Sync applications
        $user->applications()->sync($validated['application_ids']);

        return back()->with('success', "Akses aplikasi untuk {$user->name} berhasil diperbarui.");
    }
}
