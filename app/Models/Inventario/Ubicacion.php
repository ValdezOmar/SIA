<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Ubicacion extends Model
{
    use Concerns\GeneraCodigoInventario;

    protected $table = 'alm_ubicaciones';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $ubicacion): void {
            $ubicacion->codigo ??= self::codigoCorrelativo('UBI');
        });

        static::saving(function (self $ubicacion): void {
            $almacen = Almacen::query()->find($ubicacion->almacen_id);
            $user = auth()->user();

            if (! $almacen) {
                throw ValidationException::withMessages(['almacen_id' => 'El almacén seleccionado no existe.']);
            }

            if ($user && ! $user->hasAnyRole(['super_admin', 'admin'])
                && ((int) $almacen->empresa_id !== (int) $user->empresa_id
                    || ($user->sucursal_id && $almacen->sucursal_id
                        && (int) $almacen->sucursal_id !== (int) $user->sucursal_id))) {
                throw ValidationException::withMessages([
                    'almacen_id' => 'El almacén no pertenece a su empresa o sucursal.',
                ]);
            }
        });
    }

    // ========== RELACIONES ==========

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function existencias()
    {
        return $this->hasMany(ExistenciaUbicacion::class, 'ubicacion_id');
    }

    // ========== SCOPES ==========

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByAlmacen($query, $almacenId)
    {
        return $query->where('almacen_id', $almacenId);
    }

    // ========== ACCESORS ==========

    public function getUbicacionCompletaAttribute()
    {
        $partes = [];
        if ($this->pasillo) {
            $partes[] = "Pasillo {$this->pasillo}";
        }
        if ($this->estante) {
            $partes[] = "Estante {$this->estante}";
        }
        if ($this->nivel) {
            $partes[] = "Nivel {$this->nivel}";
        }
        if ($this->posicion) {
            $partes[] = "Posición {$this->posicion}";
        }

        return implode(' → ', $partes) ?: $this->codigo;
    }

    public function getUbicacionCortaAttribute()
    {
        $partes = [];
        if ($this->pasillo) {
            $partes[] = $this->pasillo;
        }
        if ($this->estante) {
            $partes[] = $this->estante;
        }
        if ($this->nivel) {
            $partes[] = $this->nivel;
        }
        if ($this->posicion) {
            $partes[] = $this->posicion;
        }

        return implode('-', $partes) ?: $this->codigo;
    }
}
