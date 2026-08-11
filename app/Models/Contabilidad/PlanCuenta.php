<?php

namespace App\Models\Contabilidad;

use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanCuenta extends Model
{
    use SoftDeletes;

    protected $table = 'con_plan_cuentas';

    protected $guarded = [];

    protected $casts = [
        'nivel' => 'integer',
        'activo' => 'boolean',
        'es_control' => 'boolean',
        'es_analitica' => 'boolean',
        'permite_movimiento' => 'boolean',
        'requiere_centro_costo' => 'boolean',
        'requiere_proyecto' => 'boolean',
    ];

    // ========== RELACIONES ==========

    public function cuentaPadre()
    {
        return $this->belongsTo(self::class, 'cuenta_padre_id');
    }

    public function subcuentas()
    {
        return $this->hasMany(self::class, 'cuenta_padre_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
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

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('tipo_cuenta', $tipo);
    }

    public function scopeByNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    public function scopeCuentasRaiz($query)
    {
        return $query->whereNull('cuenta_padre_id');
    }

    // ========== ACCESORS ==========

    public function getTipoCuentaLabelAttribute()
    {
        return match($this->tipo_cuenta) {
            'activo' => 'Activo',
            'pasivo' => 'Pasivo',
            'patrimonio' => 'Patrimonio',
            'ingreso' => 'Ingreso',
            'gasto' => 'Gasto',
            'costo' => 'Costo',
            default => $this->tipo_cuenta,
        };
    }

    public function getNaturalezaLabelAttribute()
    {
        return $this->naturaleza === 'deudora' ? 'Deudora' : 'Acreedora';
    }

    public function getNivelEspaciadoAttribute()
    {
        return str_repeat('—', max(0, $this->nivel - 1)) . ' ' . $this->codigo . ' - ' . $this->nombre;
    }

    // ========== MÉTODOS ==========

    public static function generarTrayectoria($cuentaPadreId)
    {
        if (!$cuentaPadreId) {
            return '1';
        }

        $padre = self::find($cuentaPadreId);
        if (!$padre) {
            return '1';
        }

        return $padre->trayectoria . '.' . $padre->id;
    }

    public function getSaldo($anio, $mes)
    {
        $saldo = $this->saldos()
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->first();

        if (!$saldo) {
            return [
                'debe' => 0,
                'haber' => 0,
                'saldo' => 0,
            ];
        }

        $saldoFinal = $this->naturaleza === 'deudora' 
            ? $saldo->saldo_final_debe - $saldo->saldo_final_haber
            : $saldo->saldo_final_haber - $saldo->saldo_final_debe;

        return [
            'debe' => $saldo->saldo_final_debe,
            'haber' => $saldo->saldo_final_haber,
            'saldo' => $saldoFinal,
        ];
    }

    public function getSaldoAcumulado($anio, $mes)
    {
        $saldos = $this->saldos()
            ->where('anio', $anio)
            ->where('mes', '<=', $mes)
            ->get();

        $debe = $saldos->sum('saldo_final_debe');
        $haber = $saldos->sum('saldo_final_haber');

        $saldo = $this->naturaleza === 'deudora' ? $debe - $haber : $haber - $debe;

        return [
            'debe' => $debe,
            'haber' => $haber,
            'saldo' => $saldo,
        ];
    }

    public function esPadre()
    {
        return $this->subcuentas()->count() > 0;
    }

    public function tieneMovimientos()
    {
        return $this->detallesAsiento()->count() > 0;
    }

    public function getFullNameAttribute()
    {
        return $this->codigo . ' - ' . $this->nombre;
    }
}