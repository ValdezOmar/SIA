<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class CapaCosto extends Model
{
    //
    protected $table = 'alm_capas_costos';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'datetime'
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function movimiento()
    {
        return $this->belongsTo(MovimientoInventario::class);
    }
}
