<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $email = strtolower($user->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        // Get applications that user is allowed to access
        if ($user->is_admin) {
            $accessibleApps = Application::where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $accessibleApps = $user->applications()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        $formattedApps = $accessibleApps->map(fn (Application $app) => [
            'id' => $app->id,
            'name' => $app->name,
            'slug' => $app->slug,
            'description' => $app->description,
            'icon' => $app->icon,
            'base_url' => $app->base_url,
            'launch_url' => route('sso.launch', ['application' => $app->slug]),
        ]);

        $stats = null;
        if ($user->is_admin) {
            $stats = [
                'total_apps' => Application::count(),
                'active_apps' => Application::where('is_active', true)->count(),
                'total_users' => User::count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'applications' => $formattedApps,
            'stats' => $stats,
        ]);
    }
}

