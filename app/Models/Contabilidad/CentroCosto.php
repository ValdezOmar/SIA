<?php

namespace App\Models\Contabilidad;

use App\Models\Sistema\Area;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal; // ← Agregar esta importación
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class CentroCosto extends Model
{
    use SoftDeletes;

    protected $table = 'con_centros_costos';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Boot method para asignar automáticamente empresa y sucursal
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->empresa_id = $model->empresa_id ?? Auth::user()?->empresa_id;
                $model->sucursal_id = $model->sucursal_id ?? Auth::user()?->sucursal_id;
            }
        });
    }

    // ========== RELACIONES ==========

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // Agregar relación con sucursal
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function detallesAsiento()
    {
        return $this->hasMany(AsientoDetalle::class);
    }

    public function saldos()
    {
        return $this->hasMany(SaldoCuenta::class);
    }

    // ========== SCOPES ==========

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeByEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeBySucursal($query, $sucursalId)
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    // ========== ACCESORS ==========

    public function getTipoLabelAttribute()
    {
        return match($this->tipo) {
            'costo' => 'Costo',
            'ingreso' => 'Ingreso',
            'mixto' => 'Mixto',
            default => $this->tipo,
        };
    }

    public function getFullNameAttribute()
    {
        return $this->codigo . ' - ' . $this->nombre;
    }
}
