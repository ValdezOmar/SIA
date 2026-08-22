<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'alm_movimientos_inventario';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'datetime',
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'costo_total' => 'decimal:2',
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
        return $this->hasMany(CapaCosto::class, 'kardex_id', 'kardex_id');
    }

    public function series()
    {
        return $this->hasMany(MovimientoSerie::class, 'movimiento_inventario_id');
    }

    public function lotes()
    {
        return $this->hasMany(MovimientoLote::class);
    }

    // ========== SCOPES ==========

    public function scopeEntradas($query)
    {
        return $query->whereIn('tipo', [
            'entrada_compra',
            'entrada_devolucion',
            'ajuste_positivo',
            'transferencia_entrada',
            'produccion_entrada',
        ]);
    }

    public function scopeSalidas($query)
    {
        return $query->whereIn('tipo', [
            'salida_venta',
            'salida_devolucion',
            'ajuste_negativo',
            'transferencia_salida',
            'produccion_salida',
            'salida_merma',
            'salida_despacho',
        ]);
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
            'entrada_devolucion' => 'Devolución Entrada',
            'salida_devolucion' => 'Devolución Salida',
            'salida_merma' => 'Merma',
            'salida_despacho' => 'Despacho',
            default => $this->tipo,
        };
    }
}