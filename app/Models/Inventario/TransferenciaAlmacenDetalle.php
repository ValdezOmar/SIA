<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferenciaAlmacenDetalle extends Model
{
    protected $table = 'alm_transferencias_almacenes_detalle';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'costo_unitario_salida' => 'decimal:6',
        'series' => 'array',
        'lotes' => 'array',
    ];

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(TransferenciaAlmacen::class, 'transferencia_id');
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function kardexSalida(): BelongsTo
    {
        return $this->belongsTo(Kardex::class, 'kardex_salida_id');
    }

    public function kardexEntrada(): BelongsTo
    {
        return $this->belongsTo(Kardex::class, 'kardex_entrada_id');
    }
}
