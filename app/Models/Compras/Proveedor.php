<?php

namespace App\Models\Compras;

use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    //
    protected $table = 'cmp_proveedores';

    protected $guarded = [];

    public function articulos()
    {
        return $this->hasMany(ArticuloProveedor::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
