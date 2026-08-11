<?php

namespace App\Models\Contabilidad;

use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsientoContable extends Model
{
    use SoftDeletes;

    protected $table = 'con_asientos_contables';

    protected $guarded = [];

    protected $casts = [
        'fecha_asiento' => 'date',
        'fecha_contable' => 'date',
        'fecha_autorizacion' => 'datetime',
        'total_debe' => 'decimal:6',
        'total_haber' => 'decimal:6',
    ];

    // ========== RELACIONES ==========

    public function detalles()
    {
        return $this->hasMany(AsientoDetalle::class, 'asiento_id')->orderBy('linea');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function autorizador()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // ========== SCOPES ==========

    public function scopeByPeriodo($query, $anio, $mes)
    {
        return $query->whereYear('fecha_asiento', $anio)
            ->whereMonth('fecha_asiento', $mes);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeConfirmados($query)
    {
        return $query->where('estado', 'confirmado');
    }

    // ========== ACCESORS ==========

    public function getEstadoLabelAttribute()
    {
        return match ($this->estado) {
            'borrador' => 'Borrador',
            'confirmado' => 'Confirmado',
            'anulado' => 'Anulado',
            default => $this->estado,
        };
    }

    public function getTipoLabelAttribute()
    {
        return match ($this->tipo) {
            'apertura' => 'Apertura',
            'cierre' => 'Cierre',
            'diario' => 'Diario',
            'compra' => 'Compra',
            'venta' => 'Venta',
            'ingreso' => 'Ingreso',
            'egreso' => 'Egreso',
            'ajuste' => 'Ajuste',
            'depreciacion' => 'Depreciación',
            'inventario' => 'Inventario',
            'conciliacion' => 'Conciliación',
            default => $this->tipo,
        };
    }

    public function getEstaBalanceadoAttribute()
    {
        return abs($this->total_debe - $this->total_haber) < 0.01;
    }

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', 'ASI-%')
            ->orderBy('id', 'desc')
            ->first();

        $numero = $ultimo ? intval(substr($ultimo->codigo, -6)) + 1 : 1;
        return 'ASI-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    public function getTotalDebeAttribute()
    {
        return $this->detalles()->sum('debe');
    }

    public function getTotalHaberAttribute()
    {
        return $this->detalles()->sum('haber');
    }

    public function recalcularTotales()
    {
        $this->total_debe = $this->getTotalDebeAttribute();
        $this->total_haber = $this->getTotalHaberAttribute();
        $this->save();

        return $this;
    }

    public function confirmar($usuarioId = null)
    {
        if (!$this->esta_balanceado) {
            throw new \Exception('El asiento no está balanceado. Total Debe: ' . $this->total_debe . ', Total Haber: ' . $this->total_haber);
        }

        $this->estado = 'confirmado';
        $this->autorizado_por = $usuarioId ?? auth()->id();
        $this->fecha_autorizacion = now();
        $this->save();

        // Actualizar saldos de cuentas
        $this->actualizarSaldos();

        return $this;
    }

    public function anular($motivo = null)
    {
        $this->estado = 'anulado';
        if ($motivo) {
            $this->observaciones = ($this->observaciones ? $this->observaciones . "\n" : '') . 'Anulado: ' . $motivo;
        }
        $this->save();

        // Revertir saldos
        $this->revertirSaldos();

        return $this;
    }

    private function actualizarSaldos()
    {
        foreach ($this->detalles as $detalle) {
            $mes = $this->fecha_asiento->month;
            $anio = $this->fecha_asiento->year;

            $saldo = SaldoCuenta::firstOrCreate([
                'cuenta_id' => $detalle->cuenta_id,
                'anio' => $anio,
                'mes' => $mes,
                'centro_costo_id' => $detalle->centro_costo_id,
                'proyecto_id' => $detalle->proyecto_id,
            ]);

            $saldo->movimiento_debe += $detalle->debe;
            $saldo->movimiento_haber += $detalle->haber;

            // Actualizar saldos finales
            $naturaleza = $detalle->cuenta->naturaleza;
            if ($naturaleza === 'deudora') {
                $saldo->saldo_final_debe = $saldo->saldo_inicial_debe + $saldo->movimiento_debe - $saldo->movimiento_haber;
                $saldo->saldo_final_haber = 0;
            } else {
                $saldo->saldo_final_haber = $saldo->saldo_inicial_haber + $saldo->movimiento_haber - $saldo->movimiento_debe;
                $saldo->saldo_final_debe = 0;
            }

            $saldo->save();
        }
    }

    private function revertirSaldos()
    {
        foreach ($this->detalles as $detalle) {
            $mes = $this->fecha_asiento->month;
            $anio = $this->fecha_asiento->year;

            $saldo = SaldoCuenta::where([
                'cuenta_id' => $detalle->cuenta_id,
                'anio' => $anio,
                'mes' => $mes,
                'centro_costo_id' => $detalle->centro_costo_id,
                'proyecto_id' => $detalle->proyecto_id,
            ])->first();

            if ($saldo) {
                $saldo->movimiento_debe -= $detalle->debe;
                $saldo->movimiento_haber -= $detalle->haber;

                // Recalcular saldos
                $naturaleza = $detalle->cuenta->naturaleza;
                if ($naturaleza === 'deudora') {
                    $saldo->saldo_final_debe = $saldo->saldo_inicial_debe + $saldo->movimiento_debe - $saldo->movimiento_haber;
                    $saldo->saldo_final_haber = 0;
                } else {
                    $saldo->saldo_final_haber = $saldo->saldo_inicial_haber + $saldo->movimiento_haber - $saldo->movimiento_debe;
                    $saldo->saldo_final_debe = 0;
                }

                $saldo->save();

                // Si el saldo quedó en cero, eliminar el registro
                if (
                    $saldo->saldo_final_debe == 0 && $saldo->saldo_final_haber == 0 &&
                    $saldo->movimiento_debe == 0 && $saldo->movimiento_haber == 0
                ) {
                    $saldo->delete();
                }
            }
        }
    }

    /**
     * Crear asiento desde una venta
     */
    public static function crearDesdeVenta($venta)
    {
        $asiento = self::create([
            'codigo' => self::generarCodigo(),
            'numero_asiento' => null,
            'fecha_asiento' => now(),
            'documento_tipo' => 'venta',
            'documento_id' => $venta->id,
            'documento_codigo' => $venta->codigo,
            'tipo' => 'venta',
            'concepto' => 'Venta ' . $venta->codigo . ' - Cliente: ' . $venta->cliente->nombre,
            'empresa_id' => $venta->empresa_id,
        ]);

        // Debe: Cuenta por Cobrar (Activo)
        $asiento->detalles()->create([
            'linea' => 1,
            'cuenta_id' => PlanCuenta::where('codigo', '1.1.1')->first()?->id, // Clientes
            'debe' => $venta->total,
            'haber' => 0,
            'descripcion' => 'Venta ' . $venta->codigo,
        ]);

        // Haber: Ingreso por Ventas
        $asiento->detalles()->create([
            'linea' => 2,
            'cuenta_id' => PlanCuenta::where('codigo', '4.1')->first()?->id, // Ventas
            'debe' => 0,
            'haber' => $venta->subtotal,
            'descripcion' => 'Ingreso por venta ' . $venta->codigo,
        ]);

        // Haber: IVA Débito Fiscal (si aplica)
        if ($venta->impuesto > 0) {
            $asiento->detalles()->create([
                'linea' => 3,
                'cuenta_id' => PlanCuenta::where('codigo', '2.1.3')->first()?->id, // IVA Débito Fiscal
                'debe' => 0,
                'haber' => $venta->impuesto,
                'descripcion' => 'IVA por venta ' . $venta->codigo,
            ]);
        }

        // Haber: Costo de Ventas (si existe)
        if ($venta instanceof \App\Models\Ventas\Factura) {
            $costoTotal = $venta->entregas()->sum('costo_total');
            if ($costoTotal > 0) {
                $asiento->detalles()->create([
                    'linea' => 4,
                    'cuenta_id' => PlanCuenta::where('codigo', '6.1')->first()?->id, // Costo de Ventas
                    'debe' => $costoTotal,
                    'haber' => 0,
                    'descripcion' => 'Costo de venta ' . $venta->codigo,
                ]);

                $asiento->detalles()->create([
                    'linea' => 5,
                    'cuenta_id' => PlanCuenta::where('codigo', '1.1.5')->first()?->id, // Inventario
                    'debe' => 0,
                    'haber' => $costoTotal,
                    'descripcion' => 'Salida de inventario por venta ' . $venta->codigo,
                ]);
            }
        }

        $asiento->recalcularTotales();
        $asiento->confirmar();

        return $asiento;
    }

    /**
     * Crear asiento desde una compra
     */
    public static function crearDesdeCompra($compra)
    {
        $asiento = self::create([
            'codigo' => self::generarCodigo(),
            'fecha_asiento' => now(),
            'documento_tipo' => 'compra',
            'documento_id' => $compra->id,
            'documento_codigo' => $compra->codigo,
            'tipo' => 'compra',
            'concepto' => 'Compra ' . $compra->codigo . ' - Proveedor: ' . $compra->proveedor->nombre,
            'empresa_id' => $compra->empresa_id,
        ]);

        // Debe: Inventario
        $asiento->detalles()->create([
            'linea' => 1,
            'cuenta_id' => PlanCuenta::where('codigo', '1.1.5')->first()?->id,
            'debe' => $compra->subtotal,
            'haber' => 0,
            'descripcion' => 'Compra ' . $compra->codigo,
        ]);

        // Debe: IVA Crédito Fiscal
        if ($compra->impuesto > 0) {
            $asiento->detalles()->create([
                'linea' => 2,
                'cuenta_id' => PlanCuenta::where('codigo', '1.1.4')->first()?->id, // IVA Crédito Fiscal
                'debe' => $compra->impuesto,
                'haber' => 0,
                'descripcion' => 'IVA por compra ' . $compra->codigo,
            ]);
        }

        // Haber: Cuenta por Pagar
        $asiento->detalles()->create([
            'linea' => 3,
            'cuenta_id' => PlanCuenta::where('codigo', '2.1.1')->first()?->id, // Proveedores
            'debe' => 0,
            'haber' => $compra->total,
            'descripcion' => 'Compra ' . $compra->codigo,
        ]);

        $asiento->recalcularTotales();
        $asiento->confirmar();

        return $asiento;
    }
}
