<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class ArticuloAlmacen extends Model
{
    //
    protected $table = 'alm_articulos_almacenes';

    protected $guarded = [];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }
}
