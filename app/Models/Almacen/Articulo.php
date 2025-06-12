<?php

namespace App\Models\Almacen;

use Illuminate\Database\Eloquent\Model;

class Articulo extends Model
{
    protected $table = 'alm_articulos';
    protected $fillable = [
        'codigo',
        'descripcion',
        'presentacion',
        'unidad',
        'codigo_alterno',
        'cod_almacen',
        'nombre_almacen',
        'lote',
        'fecha_ven',
        'saldo_actual',
        'empresa',
        'sn_qr'
    ];
    
}