<?php

namespace App\Models\Almacen;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'alm_inventarios';
    protected $fillable = [
        // Datos originales
        'codigo',
        'descripcion',
        'presentacion',
        'unidad',
        'codigo_alterno',
        'cod_almacen',
        'nombre_almacen',
        'lote',
        'fecha_ven',
        'sn_qr',
        'empresa',
        'saldo_actual',

        // Datos correctos (rectificación)
        'codigo_correcto',
        'descripcion_correcto',
        'presentacion_correcto',
        'unidad_correcto',
        'codigo_alterno_correcto',
        'cod_almacen_correcto',
        'nombre_almacen_correcto',
        'lote_correcto',
        'fecha_ven_correcto',
        'sn_qr_correcto',
        'empresa_correcto',

        // Datos de inventario
        'saldo_contado',
        'observacion',
        'fecha_conteo_inventario',
        'activo',
        'usuario',
    ];

    protected $casts = [
        'fecha_ven' => 'date',
        'fecha_ven_correcto' => 'date',
        'fecha_conteo_inventario' => 'date',
        'activo' => 'boolean',
    ];
}