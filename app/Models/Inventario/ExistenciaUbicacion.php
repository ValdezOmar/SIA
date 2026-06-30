<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class ExistenciaUbicacion extends Model
{
    protected $table = 'alm_existencias_ubicaciones';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:6',
    ];

    // ========== RELACIONES ==========

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }

    // ========== SCOPES ==========

    public function scopeConCantidad($query)
    {
        return $query->where('cantidad', '>', 0);
    }
}