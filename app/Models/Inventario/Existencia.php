<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Existencia extends Model
{
    protected $table = 'alm_existencias';

    protected $guarded = [];

    protected $casts = [
        'cantidad_disponible' => 'decimal:2',
        'cantidad_comprometida' => 'decimal:2',
        'cantidad_pedida' => 'decimal:2',
        'cantidad_minima' => 'decimal:2',
        'cantidad_maxima' => 'decimal:2',
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function ubicaciones()
    {
        return $this->hasMany(ExistenciaUbicacion::class, 'existencia_id');
    }

    public function getStockDisponibleAttribute()
    {
        return $this->cantidad_disponible - $this->cantidad_comprometida;
    }

    public function getEstaBajoMinimoAttribute()
    {
        return $this->cantidad_disponible <= $this->cantidad_minima;
    }

    public function getEstadoStockAttribute()
    {
        if ($this->cantidad_disponible <= 0) {
            return 'Sin Stock';
        }
        if ($this->cantidad_disponible <= $this->cantidad_minima) {
            return 'Bajo Mínimo';
        }
        if ($this->cantidad_maxima > 0 && $this->cantidad_disponible >= $this->cantidad_maxima) {
            return 'Excedido';
        }
        return 'Normal';
    }
}