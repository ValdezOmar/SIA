<?php

use Illuminate\Support\Facades\Route;

// Redirige la ruta raíz al dashboard de Filament
Route::redirect('/', '/dashboard');

// O si necesitas mantener lógica adicional:
Route::get('/', function () {
    return redirect('/dashboard');
});
