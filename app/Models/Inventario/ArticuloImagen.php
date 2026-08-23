<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticuloImagen extends Model
{
    protected $table = 'alm_articulos_imagenes';

    protected $guarded = [];

    protected $casts = [
        'principal' => 'boolean',
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }
}
