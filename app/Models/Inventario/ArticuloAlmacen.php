<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class ArticuloAlmacen extends Model
{
    protected $table = 'alm_articulos_almacenes';

    protected $guarded = [];

    protected $casts = [
        'stock_actual' => 'decimal:',
        'stock_comprometido' => 'decimal:2',
        'stock_pedido' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'costo_promedio' => 'decimal:2',
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

    // ========== SCOPES ==========

    public function scopeByArticulo($query, $articuloId)
    {
        return $query->where('articulo_id', $articuloId);
    }

    public function scopeByAlmacen($query, $almacenId)
    {
        return $query->where('almacen_id', $almacenId);
    }

    public function scopeConStock($query)
    {
        return $query->where('stock_actual', '>', 0);
    }

    public function scopeBajoMinimo($query)
    {
        return $query->whereRaw('stock_actual <= stock_minimo');
    }

    // ========== ACCESORS ==========

    public function getStockDisponibleAttribute()
    {
        return $this->stock_actual - $this->stock_comprometido;
    }

    public function getEstaBajoMinimoAttribute()
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function getEstadoStockAttribute()
    {
        if ($this->stock_actual <= 0) {
            return 'Sin Stock';
        }
        if ($this->stock_actual <= $this->stock_minimo) {
            return 'Bajo Mínimo';
        }
        if ($this->stock_actual >= $this->stock_maximo && $this->stock_maximo > 0) {
            return 'Excedido';
        }
        return 'Normal';
    }

    public function getEstadoColorAttribute()
    {
        return match($this->estado_stock) {
            'Sin Stock' => 'danger',
            'Bajo Mínimo' => 'warning',
            'Excedido' => 'info',
            'Normal' => 'success',
            default => 'gray',
        };
    }
}