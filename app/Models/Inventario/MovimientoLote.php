<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class MovimientoLote extends Model
{
    //
    protected $table = 'alm_movimientos_lotes';

    protected $guarded = [];

    public function movimiento()
    {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_id');
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }
}
