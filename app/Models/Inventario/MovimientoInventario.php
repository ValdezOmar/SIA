<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'alm_movimientos_inventario';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'datetime',
        'cantidad' => 'decimal:6',
        'costo_unitario' => 'decimal:6',
        'costo_total' => 'decimal:6',
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

    public function capasCostos()
    {
        return $this->hasMany(CapaCosto::class, 'movimiento_id');
    }

    public function series()
    {
        return $this->hasMany(MovimientoSerie::class);
    }

    public function lotes()
    {
        return $this->hasMany(MovimientoLote::class);
    }

    // ========== SCOPES ==========

    public function scopeEntradas($query)
    {
        return $query->whereIn('tipo', ['entrada_compra', 'ajuste_positivo', 'transferencia_entrada', 'produccion_entrada']);
    }

    public function scopeSalidas($query)
    {
        return $query->whereIn('tipo', ['salida_venta', 'ajuste_negativo', 'transferencia_salida', 'produccion_salida']);
    }

    // ========== ACCESORS ==========

    public function getTipoLabelAttribute()
    {
        return match($this->tipo) {
            'entrada_compra' => 'Compra',
            'salida_venta' => 'Venta',
            'ajuste_positivo' => 'Ajuste (+)', 
            'ajuste_negativo' => 'Ajuste (-)',
            'transferencia_entrada' => 'Transferencia Entrada',
            'transferencia_salida' => 'Transferencia Salida',
            'produccion_entrada' => 'Producción Entrada',
            'produccion_salida' => 'Producción Salida',
            default => $this->tipo,
        };
    }
}