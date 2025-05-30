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

Route::get('/check-upload', function() {
    $results = [
        'tmp_dir_exists' => file_exists(storage_path('app/livewire-tmp')),
        'tmp_dir_writable' => is_writable(storage_path('app/livewire-tmp')),
        'empleados_dir_exists' => file_exists(storage_path('app/public/empleados')),
        'empleados_dir_writable' => is_writable(storage_path('app/public/empleados')),
        'storage_link_exists' => file_exists(public_path('storage')),
        'php_upload_tmp_dir' => ini_get('upload_tmp_dir'),
        'free_disk_space' => disk_free_space(storage_path())
    ];

    return response()->json($results);
});