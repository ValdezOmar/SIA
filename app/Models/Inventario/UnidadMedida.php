<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    //
    protected $table = 'alm_unidades_medida';

    protected $guarded = [];

    public function articulos()
    {
        return $this->hasMany(Articulo::class);
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
