<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\EmpleadoFotoController;
use Illuminate\Support\Facades\Route;

// Redirige la ruta raíz al dashboard de Filament
Route::redirect('/', '/dashboard');

// O si necesitas mantener lógica adicional:
Route::get('/', function () {
    return redirect('/dashboard');
});
// Redirección a los dominios de llamada de Google.
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::get('/media/empleados/{empleado}/foto', EmpleadoFotoController::class)
    ->middleware('auth')
    ->name('empleados.foto');
