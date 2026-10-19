<?php

namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioArticuloHistorial extends Model
{
    protected $table = 'alm_precios_articulos_historial';

    protected $guarded = [];

    protected $casts = [
        'precio_anterior' => 'decimal:6',
        'precio_nuevo' => 'decimal:6',
        'registrado_en' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
