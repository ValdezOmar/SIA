<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Sucursal;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    protected $table = 'alm_almacenes';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ========== RELACIONES ==========

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class, 'almacen_id');
    }

    public function existencias()
    {
        return $this->hasMany(Existencia::class, 'almacen_id');
    }

    public function articulos()
    {
        return $this->hasMany(ArticuloAlmacen::class);
    }

    public function series()
    {
        return $this->hasMany(Serie::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function capasCostos()
    {
        return $this->hasMany(CapaCosto::class);
    }

    // ========== SCOPES ==========

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBySucursal($query, $sucursalId)
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    // ========== MÉTODOS ÚTILES ==========

    /**
     * Obtener el stock total de un artículo en este almacén
     */
    public function stockArticulo($articuloId)
    {
        return $this->existencias()
            ->where('articulo_id', $articuloId)
            ->value('cantidad_disponible') ?? 0;
    }

    /**
     * Obtener todas las existencias de un artículo en este almacén
     */
    public function existenciasArticulo($articuloId)
    {
        return $this->existencias()
            ->where('articulo_id', $articuloId)
            ->first();
    }
}