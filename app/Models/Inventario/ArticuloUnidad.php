<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class ArticuloUnidad extends Model
{
    //
    protected $table = 'alm_articulos_unidades';

    protected $guarded = [];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }
}
