<?php

use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider or bootstrap/app.php
| within a group which is assigned the "api" middleware group.
|
*/

// SSO Ticket Verification (Server-to-Server)
Route::post('/sso/verify', [SsoController::class, 'verify'])->name('api.sso.verify');
