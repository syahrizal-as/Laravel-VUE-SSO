<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\SsoTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Launch application: generate one-time ticket and redirect user to target app callback.
     */
    public function launch(Request $request, Application $application): RedirectResponse
    {
        $user = $request->user();

        if (! $application->is_active) {
            abort(403, 'Aplikasi ini sedang tidak aktif.');
        }

        if (! $user->hasAccessTo($application)) {
            abort(403, 'Anda belum memiliki izin akses ke aplikasi ini. Hubungi Administrator.');
        }

        // Generate one-time secure SSO ticket (valid for 2 minutes)
        $ticketString = Str::random(64);
        SsoTicket::create([
            'ticket' => $ticketString,
            'user_id' => $user->id,
            'application_id' => $application->id,
            'expires_at' => now()->addMinutes(2),
        ]);

        $separator = str_contains($application->callback_url, '?') ? '&' : '?';
        $redirectUrl = $application->callback_url . $separator . 'ticket=' . $ticketString;

        return redirect()->away($redirectUrl);
    }

    /**
     * Verify SSO ticket from client application (server-to-server).
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'ticket' => 'required|string',
        ]);

        $application = Application::where('client_id', $request->client_id)
            ->where('is_active', true)
            ->first();

        if (! $application || ! hash_equals($application->client_secret, $request->client_secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Client ID atau Client Secret tidak valid.',
            ], 401);
        }

        $ticket = SsoTicket::where('ticket', $request->ticket)
            ->where('application_id', $application->id)
            ->valid()
            ->first();

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket SSO tidak valid atau sudah kadaluwarsa.',
            ], 400);
        }

        // Invalidate ticket so it cannot be reused
        $ticket->markAsUsed();

        $user = $ticket->user;
        $pivot = $application->users()->where('users.id', $user->id)->first()?->pivot;
        $roleInApp = $pivot ? $pivot->role_in_app : ($user->is_admin ? 'admin' : 'user');

        return response()->json([
            'success' => true,
            'message' => 'SSO ticket verified successfully.',
            'data' => [
                'sso_user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'is_admin' => (bool) $user->is_admin,
                'role_in_app' => $roleInApp,
            ],
        ]);
    }
}
