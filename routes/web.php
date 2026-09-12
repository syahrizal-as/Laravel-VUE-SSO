<?php

use App\Http\Controllers\Admin\ApplicationManagementController;
use App\Http\Controllers\Admin\UserAccessManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Direct /dashboard fallback (redirect to user's current team dashboard)
Route::get('dashboard', function () {
    $user = auth()->user();
    $currentTeam = $user->currentTeam ?? $user->personalTeam();
    if ($currentTeam) {
        return redirect()->route('dashboard', ['current_team' => $currentTeam->slug]);
    }
    return app(DashboardController::class)(request());
})->middleware('auth')->name('dashboard.redirect');

// SSO Launch Route
Route::get('apps/{application:slug}/launch', [SsoController::class, 'launch'])
    ->middleware(['auth'])
    ->name('sso.launch');

// Admin Management Routes
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureAdmin::class])
    ->group(function () {
        // Applications CRUD
        Route::get('applications', [ApplicationManagementController::class, 'index'])->name('applications.index');
        Route::post('applications', [ApplicationManagementController::class, 'store'])->name('applications.store');
        Route::put('applications/{application}', [ApplicationManagementController::class, 'update'])->name('applications.update');
        Route::delete('applications/{application}', [ApplicationManagementController::class, 'destroy'])->name('applications.destroy');
        Route::post('applications/{application}/regenerate-secret', [ApplicationManagementController::class, 'regenerateSecret'])->name('applications.regenerate-secret');

        // User Access Management
        Route::get('users', [UserAccessManagementController::class, 'index'])->name('users.index');
        Route::put('users/{user}/access', [UserAccessManagementController::class, 'updateAccess'])->name('users.access.update');
    });

// Team Dashboard
Route::prefix('{current_team}')
    ->middleware(['auth', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
