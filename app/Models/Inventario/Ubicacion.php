<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    protected $table = 'alm_ubicaciones';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

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
        if ($this->pasillo) $partes[] = "Pasillo {$this->pasillo}";
        if ($this->estante) $partes[] = "Estante {$this->estante}";
        if ($this->nivel) $partes[] = "Nivel {$this->nivel}";
        if ($this->posicion) $partes[] = "Posición {$this->posicion}";
        
        return implode(' → ', $partes) ?: $this->codigo;
    }

    public function getUbicacionCortaAttribute()
    {
        $partes = [];
        if ($this->pasillo) $partes[] = $this->pasillo;
        if ($this->estante) $partes[] = $this->estante;
        if ($this->nivel) $partes[] = $this->nivel;
        if ($this->posicion) $partes[] = $this->posicion;
        
        return implode('-', $partes) ?: $this->codigo;
    }
}