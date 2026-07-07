<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;

class ListaPrecio extends Model
{
    //
    protected $table = 'alm_listas_precios';

    protected $guarded = [];

    public function precios()
    {
        return $this->hasMany(PrecioArticulo::class, 'lista_precio_id');
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
