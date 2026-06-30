<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class ArticuloImagen extends Model
{
    //
    protected $table = 'alm_articulos_imagenes';

    protected $guarded = [];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
