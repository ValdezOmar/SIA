<?php

namespace App\Models\Compras;

use Illuminate\Database\Eloquent\Model;

class ArticuloProveedor extends Model
{
    //
    protected $table = 'cmp_articulos_proveedores';

    protected $guarded = [];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
