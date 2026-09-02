<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Almacen extends Model
{
    use Concerns\GeneraCodigoInventario;

    protected $table = 'alm_almacenes';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $almacen): void {
            $almacen->codigo ??= self::codigoCorrelativo('ALM');
            $almacen->empresa_id ??= auth()->user()?->empresa_id;
        });

        static::saving(function (self $almacen): void {
            if (! $almacen->empresa_id) {
                throw ValidationException::withMessages(['empresa_id' => 'Debe asignar una empresa al almacén.']);
            }

            if ($almacen->sucursal_id && ! Sucursal::query()
                ->whereKey($almacen->sucursal_id)
                ->where('empresa_id', $almacen->empresa_id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'sucursal_id' => 'La sucursal seleccionada no pertenece a la empresa del almacén.',
                ]);
            }
        });
    }

    // ========== RELACIONES ==========

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
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

    public function kardex()
    {
        return $this->hasMany(Kardex::class);
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

    public function scopeByEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
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
