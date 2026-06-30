<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class LoteStock extends Model
{
    protected $table = 'alm_lotes_stock';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:6',
    ];

    // ========== RELACIONES ==========

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    // ========== SCOPES ==========

    public function scopeConCantidad($query)
    {
        return $query->where('cantidad', '>', 0);
    }
}