<?php

namespace App\Models\Contabilidad;

use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;

class SaldoCuenta extends Model
{
    protected $table = 'con_saldos_cuentas';

    protected $guarded = [];

    protected $casts = [
        'saldo_inicial_debe' => 'decimal:6',
        'saldo_inicial_haber' => 'decimal:6',
        'movimiento_debe' => 'decimal:6',
        'movimiento_haber' => 'decimal:6',
        'saldo_final_debe' => 'decimal:6',
        'saldo_final_haber' => 'decimal:6',
    ];

    // ========== RELACIONES ==========

    public function cuenta()
    {
        return $this->belongsTo(PlanCuenta::class);
    }

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class);
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // ========== SCOPES ==========

    public function scopeByPeriodo($query, $anio, $mes)
    {
        return $query->where('anio', $anio)->where('mes', $mes);
    }

    // ========== ACCESORS ==========

    public function getSaldoFinalAttribute()
    {
        $cuenta = $this->cuenta;
        if (!$cuenta) return 0;

        return $cuenta->naturaleza === 'deudora'
            ? $this->saldo_final_debe - $this->saldo_final_haber
            : $this->saldo_final_haber - $this->saldo_final_debe;
    }

    public function getSaldoInicialAttribute()
    {
        $cuenta = $this->cuenta;
        if (!$cuenta) return 0;

        return $cuenta->naturaleza === 'deudora'
            ? $this->saldo_inicial_debe - $this->saldo_inicial_haber
            : $this->saldo_inicial_haber - $this->saldo_inicial_debe;
    }

    public function getPeriodoLabelAttribute()
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return ($meses[$this->mes] ?? $this->mes) . ' ' . $this->anio;
    }

    // ========== MÉTODOS ==========

    public static function calcularSaldoInicial($cuentaId, $anio, $mes, $centroCostoId = null, $proyectoId = null)
    {
        $saldoAnterior = self::where('cuenta_id', $cuentaId)
            ->where('anio', $anio)
            ->where('mes', $mes - 1)
            ->where('centro_costo_id', $centroCostoId)
            ->where('proyecto_id', $proyectoId)
            ->first();

        if ($saldoAnterior) {
            return [
                'debe' => $saldoAnterior->saldo_final_debe,
                'haber' => $saldoAnterior->saldo_final_haber,
            ];
        }

        return ['debe' => 0, 'haber' => 0];
    }

    public static function generarSaldosMensuales($anio, $mes)
    {
        // Obtener todas las cuentas activas
        $cuentas = PlanCuenta::where('activo', true)->get();

        foreach ($cuentas as $cuenta) {
            $saldoInicial = self::calcularSaldoInicial($cuenta->id, $anio, $mes);

            self::updateOrCreate(
                [
                    'cuenta_id' => $cuenta->id,
                    'anio' => $anio,
                    'mes' => $mes,
                    'centro_costo_id' => null,
                    'proyecto_id' => null,
                ],
                [
                    'saldo_inicial_debe' => $saldoInicial['debe'],
                    'saldo_inicial_haber' => $saldoInicial['haber'],
                    'movimiento_debe' => 0,
                    'movimiento_haber' => 0,
                    'saldo_final_debe' => $saldoInicial['debe'],
                    'saldo_final_haber' => $saldoInicial['haber'],
                    'naturaleza' => $cuenta->naturaleza,
                ]
            );
        }
    }
}