<?php

namespace App\Models\Contabilidad;

use Illuminate\Database\Eloquent\Model;

class AsientoDetalle extends Model
{
    protected $table = 'con_asientos_detalle';

    protected $guarded = [];

    protected $casts = [
        'debe' => 'decimal:6',
        'haber' => 'decimal:6',
        'linea' => 'integer',
        'datos_adicionales' => 'array',
    ];

    // ========== RELACIONES ==========

    // ✅ Cambiar el nombre de la relación para que coincida con la migración
    public function asiento()
    {
        return $this->belongsTo(AsientoContable::class, 'asiento_id');
    }

    public function cuenta()
    {
        return $this->belongsTo(PlanCuenta::class, 'cuenta_id');
    }

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    // ========== ACCESORS ==========

    public function getMontoAttribute()
    {
        return $this->debe > 0 ? $this->debe : $this->haber;
    }

    public function getTipoMovimientoAttribute()
    {
        return $this->debe > 0 ? 'Debe' : 'Haber';
    }
}