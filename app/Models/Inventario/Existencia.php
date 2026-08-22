<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Existencia extends Model
{
    protected $table = 'alm_existencias';

    protected $guarded = [];

    protected $casts = [
        'cantidad_disponible' => 'decimal:6',
        'cantidad_comprometida' => 'decimal:6',
        'cantidad_pedida' => 'decimal:6',
        'cantidad_minima' => 'decimal:6',
        'cantidad_maxima' => 'decimal:6',
        'costo_promedio' => 'decimal:6',
        'costo_acumulado' => 'decimal:6',
        'ultimo_costo' => 'decimal:6',
        'ultima_entrada' => 'datetime',
        'ultima_salida' => 'datetime',
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

    public function scopeDisponible($query)
    {
        return $query->where('cantidad_disponible', '>', 0);
    }

    public function scopeBajoMinimo($query)
    {
        return $query->whereRaw('cantidad_disponible <= cantidad_minima');
    }

    // ========== ACCESORS ==========

    public function getStockTotalAttribute()
    {
        return $this->cantidad_disponible - $this->cantidad_comprometida;
    }

    public function getEstaBajoMinimoAttribute()
    {
        return $this->cantidad_disponible <= $this->cantidad_minima;
    }
}