<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Teams\CreateTeam;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google OAuth callback.
     */
    public function callback(Request $request, CreateTeam $createTeam): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception $e) {
            Log::error('Google OAuth error: ' . $e->getMessage());

            return redirect()->route('login')->withErrors([
                'google' => 'Gagal login melalui Google: ' . $e->getMessage(),
            ]);
        }

        $email = $googleUser->getEmail();
        if (! $email) {
            return redirect()->route('login')->withErrors([
                'google' => 'Akun Google Anda tidak memiliki email publik.',
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {
            $isFirstUser = User::count() === 0;

            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User',
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'is_admin' => $isFirstUser, // First registered user is automatically Super Admin
                'email_verified_at' => now(),
            ]);
        }

        // Ensure user has a personal team
        $currentTeam = $user->currentTeam ?? $user->personalTeam();
        if (! $currentTeam) {
            $teamName = explode(' ', $user->name)[0] . "'s Team";
            $currentTeam = $createTeam->handle($user, $teamName, true);
        } else {
            $user->switchTeam($currentTeam);
        }

        if ($currentTeam) {
            URL::defaults(['current_team' => $currentTeam->slug]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', ['current_team' => $currentTeam->slug]));
    }
}
