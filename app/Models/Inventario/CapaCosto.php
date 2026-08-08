<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class CapaCosto extends Model
{
    protected $table = 'alm_capas_costos';

    protected $guarded = [];

    protected $casts = [
        'cantidad_original' => 'decimal:2',
        'cantidad_disponible' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'fecha' => 'datetime',
        'activo' => 'boolean',
    ];

    // ========== RELACIONES ==========

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function kardex()
    {
        return $this->belongsTo(Kardex::class);
    }

    // ========== SCOPES ==========

    public function scopeDisponible($query)
    {
        return $query->where('cantidad_disponible', '>', 0)->where('activo', true);
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    // ========== ACCESORS ==========

    public function getCostoTotalAttribute()
    {
        return $this->cantidad_disponible * $this->costo_unitario;
    }
}