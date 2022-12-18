<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\TramiteController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\DerivacionController;
use App\Http\Controllers\CiteInternoController;
use App\Http\Controllers\TipoPermisoController;
use App\Http\Controllers\FilePersonalController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\RemitenteExternoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {return view('welcome');});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::prefix('/')
    ->middleware('auth')
    ->group(function () {
        Route::resource('file-personal', FilePersonalController::class);
        Route::resource('users', UserController::class);
        Route::resource('cargos', CargoController::class);
        Route::resource('areas', AreaController::class);
        Route::resource('cite-internos', CiteInternoController::class);
        Route::resource('derivaciones', DerivacionController::class);
        Route::get('permisos', [PermisosController::class, 'index'])->name('permisos.index');
        Route::post('permisos', [PermisosController::class, 'store'])->name('permisos.store');
        Route::get('permisos/create', [PermisosController::class,])->name('permisos.create');
        Route::get('permisos/{permisos}', [PermisosController::class, 'show',])->name('permisos.show');
        Route::get('permisos/{permisos}/edit', [PermisosController::class,'edit',])->name('permisos.edit');
        Route::put('permisos/{permisos}', [PermisosController::class,'update',])->name('permisos.update');
        Route::delete('permisos/{permisos}', [PermisosController::class,'destroy',])->name('permisos.destroy');

        Route::resource('tramites', TramiteController::class);
        Route::resource('remitente-externos', RemitenteExternoController::class);
        Route::resource('tipo-documentos', TipoDocumentoController::class);
        Route::resource('tipo-permisos', TipoPermisoController::class);
    });
