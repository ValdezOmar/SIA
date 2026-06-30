<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class ArticuloAtributo extends Model
{
    protected $table = 'alm_articulos_atributos';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== RELACIONES ==========

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function atributo()
    {
        return $this->belongsTo(Atributo::class);
    }

    // ========== ACCESORS ==========

    public function getNombreAtributoAttribute()
    {
        return $this->atributo?->nombre ?? 'N/A';
    }

    public function getCodigoAtributoAttribute()
    {
        return $this->atributo?->codigo ?? 'N/A';
    }
}