<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class MovimientoSerie extends Model
{
    //
    protected $table = 'alm_movimientos_series';

    protected $guarded = [];

    public function movimiento()
    {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_id');
    }

    public function serie()
    {
        return $this->belongsTo(Serie::class);
    }
}
