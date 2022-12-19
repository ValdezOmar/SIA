<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Correspondencia\CorCiteController;
use App\Http\Controllers\Empleado\UserController;
use App\Http\Controllers\empleado\EmpleadoController;
use App\Http\Controllers\Empleado\PersonalController;
use App\Http\Controllers\Correspondencia\CorHojaRutaController;
use App\Http\Controllers\Correspondencia\CorTramiteController;
use App\Http\Controllers\Correspondencia\CorBandejaController;


// Route::get('sia', [AdminController::class, 'index']);
Route::get('/', [EmpleadoController::class,'index'])->name('empleado.index');
Route::get('hojarutas.derivacion', [CorHojaRutaController::class, 'crearDerivacion'])->name('correspondencia.crearDerivacion');
Route::get('bandeja', [CorBandejaController::class, 'bandejaEntrada'])->name('correspondencia.bandeja.entrada');
Route::get('bandeja.{hojaruta}', [CorBandejaController::class, 'verTramite'])->name('correspondencia.bandeja.verTramite');
//Route::resource('usuario',UserController::class)->names('empleado.user');
Route::resource('empleado', EmpleadoController::class)->names('empleado.empleado');
Route::resource('personal',PersonalController::class)->names('empleado.personal');
Route::resource('hojarutas',CorHojaRutaController::class)->names('correspondencia.hojaruta');
Route::resource('tramites',CorTramiteController::class)->names('correspondencia.tramite');
Route::resource('cites', CorCiteController::class)->names('correspondencia.cite');
// Route::resource('bandeja', CorBandejaController::class)->names('correspondencia.bandeja');

