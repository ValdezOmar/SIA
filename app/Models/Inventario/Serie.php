<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    //
    protected $table = 'alm_series';

    protected $guarded = [];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoSerie::class);
    }
    
}
