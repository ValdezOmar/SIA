<?php

namespace App\Models\Contabilidad;

use App\Models\Sistema\Area;
use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentroCosto extends Model
{
    use SoftDeletes;

    protected $table = 'con_centros_costos';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ========== RELACIONES ==========

    // ✅ Cambiar de departamento a area
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