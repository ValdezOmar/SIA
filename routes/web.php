<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use Laravel\Socialite\Facades\Socialite;

// Redirige la ruta raíz al dashboard de Filament
Route::redirect('/', '/dashboard');

// O si necesitas mantener lógica adicional:
Route::get('/', function () {
    return redirect('/dashboard');
});
//Redireccion a los dominios de llamada de google
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');