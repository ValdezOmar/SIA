<?php

namespace App\Models\Contabilidad;

use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->linea) && !empty($model->asiento_id)) {
                $ultimo = self::where('asiento_id', $model->asiento_id)
                    ->orderByDesc('linea')
                    ->value('linea');

                $model->linea = ($ultimo ?? 0) + 1;
            }

            if (Auth::check()) {
                $model->empresa_id = $model->empresa_id ?? Auth::user()?->empresa_id;
                $model->sucursal_id = $model->sucursal_id ?? Auth::user()?->sucursal_id;
            }
        });
    }

    // ========== RELACIONES ==========

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

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
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