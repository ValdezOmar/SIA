<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CotizacionPdfController;
use App\Http\Controllers\EmpleadoFotoController;
use Illuminate\Support\Facades\Route;

// Redirige la ruta raíz al dashboard de Filament.
Route::redirect('/', '/dashboard');

Route::get('/ventas/cotizaciones/{cotizacion}/pdf', CotizacionPdfController::class)
    ->middleware('auth')
    ->name('cotizaciones.pdf');

// Redirección a los dominios de llamada de Google.
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::get('/media/empleados/{empleado}/foto', EmpleadoFotoController::class)
    ->middleware('auth')
    ->name('empleados.foto');
