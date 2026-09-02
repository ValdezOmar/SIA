<?php

namespace App\Models\Contabilidad;

use App\Models\Inventario\Kardex;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use App\Models\Ventas\Factura;
use App\Models\Ventas\Pago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $model->codigo = self::generarCodigo();
            }

            if (Auth::check()) {
                $model->creado_por = Auth::id();
                $model->empresa_id = $model->empresa_id ?? Auth::user()?->empresa_id;
                $model->sucursal_id = $model->sucursal_id ?? Auth::user()?->sucursal_id;
            }
        });
    }

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

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoContable::class);
    }

    public function kardex()
    {
        return $this->belongsTo(Kardex::class, 'documento_id');
    }

    // ========== SCOPES ==========

    public function scopeByEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeBySucursal($query, $sucursalId)
    {
        return $query->where('sucursal_id', $sucursalId);
    }

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

        return 'ASI-'.str_pad($numero, 6, '0', STR_PAD_LEFT);
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
        return DB::transaction(function () use ($usuarioId) {
            self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $this->refresh();

            if ($this->estado === 'confirmado') {
                return $this;
            }

            if ($this->estado === 'anulado') {
                throw new RuntimeException('No se puede confirmar un asiento anulado.');
            }

            $this->validarAntesDeConfirmar();

            if (! $this->esta_balanceado) {
                throw new \Exception('El asiento no está balanceado. Total Debe: '.$this->total_debe.', Total Haber: '.$this->total_haber);
            }

            $this->estado = 'confirmado';
            $this->autorizado_por = $usuarioId ?? Auth::id();
            $this->fecha_autorizacion = now();
            $this->save();

            // Actualizar saldos de cuentas
            $this->actualizarSaldos();

            return $this;
        });
    }

    public function anular($motivo = null)
    {
        return DB::transaction(function () use ($motivo) {
            self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $this->refresh();

            if ($this->estado === 'anulado') {
                return $this;
            }

            if ($this->estado !== 'confirmado') {
                throw new RuntimeException('Solo se pueden anular asientos confirmados.');
            }

            $this->estado = 'anulado';
            if ($motivo) {
                $this->observaciones = ($this->observaciones ? $this->observaciones."\n" : '').'Anulado: '.$motivo;
            }
            $this->save();

            // Revertir saldos
            $this->revertirSaldos();

            return $this;
        });
    }

    private function validarAntesDeConfirmar(): void
    {
        $detalles = $this->detalles()->with('cuenta')->get();

        if ($detalles->count() < 2) {
            throw new RuntimeException('El asiento debe incluir al menos dos partidas.');
        }

        foreach ($detalles as $detalle) {
            if (! $detalle->cuenta || ! $detalle->cuenta->activo || ! $detalle->cuenta->permite_movimiento) {
                throw new RuntimeException('Cada partida debe usar una cuenta activa que permita movimientos.');
            }

            if (((float) $detalle->debe > 0) === ((float) $detalle->haber > 0)) {
                throw new RuntimeException('Cada partida debe tener importe únicamente en Debe o únicamente en Haber.');
            }
        }

        if (! $this->esta_balanceado || (float) $this->total_debe <= 0) {
            throw new RuntimeException('El asiento debe estar balanceado y tener un importe mayor a cero.');
        }

        $periodo = PeriodoContable::query()
            ->where('empresa_id', $this->empresa_id)
            ->where('anio', $this->fecha_asiento->year)
            ->where('mes', $this->fecha_asiento->month)
            ->when($this->sucursal_id, fn ($query) => $query->where('sucursal_id', $this->sucursal_id))
            ->first();

        if ($periodo && ! $periodo->estaAbierto()) {
            throw new RuntimeException('El período contable de este asiento está cerrado o bloqueado.');
        }
    }

    private function actualizarSaldos()
    {
        foreach ($this->detalles as $detalle) {
            $mes = $this->fecha_asiento->month;
            $anio = $this->fecha_asiento->year;

            $saldo = SaldoCuenta::firstOrCreate(
                [
                    'cuenta_id' => $detalle->cuenta_id,
                    'anio' => $anio,
                    'mes' => $mes,
                    'centro_costo_id' => $detalle->centro_costo_id,
                    'proyecto_id' => $detalle->proyecto_id,
                ],
                [
                    'empresa_id' => $this->empresa_id,
                    'sucursal_id' => $this->sucursal_id,
                    'naturaleza' => $detalle->cuenta->naturaleza,
                ],
            );

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

    protected static function obtenerOCrearCuenta(string $codigo, string $nombre, string $tipoCuenta, string $naturaleza): ?PlanCuenta
    {
        $cuenta = PlanCuenta::where('codigo', $codigo)->first();

        if ($cuenta) {
            return $cuenta;
        }

        return PlanCuenta::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'nombre_completo' => $nombre,
            'descripcion' => 'Cuenta creada automáticamente para el flujo de ventas.',
            'cuenta_padre_id' => null,
            'nivel' => 1,
            'trayectoria' => $codigo,
            'tipo_cuenta' => $tipoCuenta,
            'naturaleza' => $naturaleza,
            'tipo_detalle' => 'general',
            'es_control' => false,
            'es_analitica' => false,
            'permite_movimiento' => true,
            'requiere_centro_costo' => false,
            'requiere_proyecto' => false,
            'activo' => true,
            'empresa_id' => Auth::user()?->empresa_id,
            'sucursal_id' => Auth::user()?->sucursal_id,
        ]);
    }

    /**
     * Contabiliza el efecto valorizado de un movimiento Kardex.
     *
     * Compra/venta documentadas usan sus procesos comerciales; este método cubre
     * los movimientos que nacen directamente en Kardex. Transferencias internas
     * y consignaciones de terceros no alteran el patrimonio y, por ello, no crean
     * asiento. Un despacho se reclasifica a inventario en tránsito hasta la venta.
     */
    public static function crearDesdeKardex(Kardex $kardex, bool $permitirAnulado = false): ?self
    {
        if ((! $permitirAnulado && $kardex->estado !== 'confirmado') || (float) $kardex->costo_total <= 0) {
            return null;
        }

        if (in_array($kardex->tipo_movimiento, ['transferencia_entrada', 'transferencia_salida', 'consignacion'], true)) {
            return null;
        }

        $existente = self::query()
            ->where('documento_tipo', 'kardex')
            ->where('documento_id', $kardex->id)
            ->first();

        if ($existente) {
            return $existente;
        }

        if ($kardex->movimiento_relacionado_id) {
            $asientoOriginal = self::query()
                ->where('documento_tipo', 'kardex')
                ->where('documento_id', $kardex->movimiento_relacionado_id)
                ->where('estado', 'confirmado')
                ->with('detalles')
                ->first();

            if (! $asientoOriginal) {
                $movimientoOriginal = Kardex::find($kardex->movimiento_relacionado_id);
                if ($movimientoOriginal) {
                    $asientoOriginal = self::crearDesdeKardex($movimientoOriginal, true)?->load('detalles');
                }
            }

            if ($asientoOriginal) {
                return self::crearReversionKardex($kardex, $asientoOriginal);
            }
        }

        $cuentas = [
            'inventario' => ['1.1.5', 'Inventario', 'activo', 'deudora'],
            'transito' => ['1.1.6', 'Inventario despachado en tránsito', 'activo', 'deudora'],
            'produccion' => ['1.1.7', 'Producción en proceso', 'activo', 'deudora'],
            'puente' => ['2.1.9', 'Contrapartida transitoria de inventario', 'pasivo', 'acreedora'],
            'apertura' => ['3.1.9', 'Contrapartida de inventario inicial', 'patrimonio', 'acreedora'],
            'ganancia' => ['4.9.1', 'Ganancia por ajuste de inventario', 'ingreso', 'acreedora'],
            'costo_venta' => ['6.1', 'Costo de Ventas', 'costo', 'deudora'],
            'perdida' => ['6.2.1', 'Pérdida por ajuste de inventario', 'gasto', 'deudora'],
            'merma' => ['6.2.2', 'Gasto por merma de inventario', 'gasto', 'deudora'],
        ];

        $par = match ($kardex->tipo_movimiento) {
            'compra' => ['inventario', 'puente'],
            'venta' => ['costo_venta', 'inventario'],
            'ajuste_incremento' => ['inventario', 'ganancia'],
            'ajuste_decremento' => ['perdida', 'inventario'],
            'ajuste_fisico' => $kardex->direccion === 'entrada'
                ? ['inventario', 'ganancia']
                : ['perdida', 'inventario'],
            'devolucion_compra' => ['puente', 'inventario'],
            'devolucion_venta' => ['inventario', 'costo_venta'],
            'produccion_salida' => ['produccion', 'inventario'],
            'produccion_entrada' => ['inventario', 'produccion'],
            'inventario_inicial' => ['inventario', 'apertura'],
            'merma' => ['merma', 'inventario'],
            'despacho' => ['transito', 'inventario'],
            default => null,
        };

        if (! $par) {
            throw new RuntimeException('No existe configuración contable para el movimiento Kardex '.$kardex->tipo_movimiento.'.');
        }

        [$claveDebe, $claveHaber] = $par;
        $cuentaDebe = self::obtenerOCrearCuenta(...$cuentas[$claveDebe]);
        $cuentaHaber = self::obtenerOCrearCuenta(...$cuentas[$claveHaber]);
        $importe = (float) $kardex->costo_total;
        $descripcion = $kardex->tipo_movimiento_label.' Kardex #'.$kardex->id;

        $asiento = self::create([
            'fecha_asiento' => $kardex->fecha_contable ?? $kardex->fecha_movimiento,
            'fecha_contable' => $kardex->fecha_contable ?? $kardex->fecha_movimiento,
            'documento_tipo' => 'kardex',
            'documento_id' => $kardex->id,
            'documento_codigo' => $kardex->documento_codigo ?? 'KAR-'.$kardex->id,
            'tipo' => $kardex->tipo_movimiento === 'inventario_inicial' ? 'apertura' : 'inventario',
            'concepto' => $descripcion,
            'observaciones' => $kardex->motivo ?: $kardex->observaciones,
            'empresa_id' => $kardex->empresa_id,
        ]);

        $baseDetalle = [
            'empresa_id' => $kardex->empresa_id,
            'referencia' => $kardex->documento_codigo,
            'datos_adicionales' => ['kardex_id' => $kardex->id, 'tipo_movimiento' => $kardex->tipo_movimiento],
        ];
        $asiento->detalles()->create($baseDetalle + [
            'linea' => 1, 'cuenta_id' => $cuentaDebe->id, 'debe' => $importe, 'haber' => 0, 'descripcion' => $descripcion,
        ]);
        $asiento->detalles()->create($baseDetalle + [
            'linea' => 2, 'cuenta_id' => $cuentaHaber->id, 'debe' => 0, 'haber' => $importe, 'descripcion' => $descripcion,
        ]);
        $asiento->recalcularTotales();
        $asiento->confirmar();

        return $asiento;
    }

    private static function crearReversionKardex(Kardex $kardex, self $original): self
    {
        $descripcion = 'Reversión '.$original->concepto.' mediante Kardex #'.$kardex->id;
        $asiento = self::create([
            'fecha_asiento' => $kardex->fecha_contable ?? $kardex->fecha_movimiento,
            'fecha_contable' => $kardex->fecha_contable ?? $kardex->fecha_movimiento,
            'documento_tipo' => 'kardex',
            'documento_id' => $kardex->id,
            'documento_codigo' => $kardex->documento_codigo ?? 'KAR-'.$kardex->id,
            'tipo' => 'inventario',
            'concepto' => $descripcion,
            'observaciones' => $kardex->observaciones,
            'empresa_id' => $kardex->empresa_id,
        ]);

        foreach ($original->detalles as $detalle) {
            $asiento->detalles()->create([
                'linea' => $detalle->linea,
                'cuenta_id' => $detalle->cuenta_id,
                'debe' => $detalle->haber,
                'haber' => $detalle->debe,
                'descripcion' => $descripcion,
                'empresa_id' => $kardex->empresa_id,
                'referencia' => $kardex->documento_codigo,
                'datos_adicionales' => [
                    'kardex_id' => $kardex->id,
                    'asiento_original_id' => $original->id,
                    'es_reversion' => true,
                ],
            ]);
        }

        $asiento->recalcularTotales();
        $asiento->confirmar();

        return $asiento;
    }

    /**
     * Crear asiento desde una venta
     */
    public static function crearDesdeVenta($venta, $fechaContable = null)
    {
        $yaExiste = self::where('documento_tipo', 'venta')
            ->where('documento_id', $venta->id)
            ->first();

        if ($yaExiste?->estado === 'confirmado') {
            return $yaExiste;
        }

        if ($yaExiste) {
            $yaExiste->detalles()->delete();
            $yaExiste->delete();
        }

        $documentoCodigo = $venta->numero ?? $venta->codigo ?? 'VENTA-'.$venta->id;
        $clienteNombre = $venta->cliente?->nombre ?? $venta->cliente?->nombre_comercial ?? 'Cliente';
        $subtotalDeclarado = round((float) ($venta->subtotal ?? $venta->detalles()->sum('subtotal')), 6);
        $impuestoDeclarado = round((float) ($venta->impuesto ?? $venta->detalles()->sum('impuesto')), 6);
        $total = round((float) ($venta->total ?? $venta->detalles()->sum('total')), 6);

        if ($total <= 0) {
            throw new RuntimeException('La venta debe tener un total mayor a cero antes de generar su asiento contable.');
        }

        // El total de la factura es el importe exigible al cliente y, por tanto,
        // la fuente de verdad del asiento. El ingreso neto se deriva descontando
        // el impuesto para garantizar Debe = Haber incluso en documentos antiguos
        // cuyos campos subtotal/impuesto quedaron calculados con criterios distintos.
        $impuesto = min(max($impuestoDeclarado, 0), $total);
        $subtotal = round($total - $impuesto, 6);

        if (abs(($subtotalDeclarado + $impuestoDeclarado) - $total) > 0.005) {
            \Illuminate\Support\Facades\Log::warning('Se normalizaron importes al contabilizar una venta desbalanceada.', [
                'factura_id' => $venta->id,
                'subtotal_declarado' => $subtotalDeclarado,
                'impuesto_declarado' => $impuestoDeclarado,
                'total' => $total,
                'ingreso_neto_contabilizado' => $subtotal,
            ]);
        }
        $costoTotal = (float) \App\Models\Inventario\Kardex::where('documento_tipo', 'venta')
            ->where('documento_id', $venta->id)
            ->sum('costo_total');

        $asiento = self::create([
            'codigo' => self::generarCodigo(),
            'numero_asiento' => null,
            'fecha_asiento' => $fechaContable ?? now(),
            'fecha_contable' => $fechaContable,
            'documento_tipo' => 'venta',
            'documento_id' => $venta->id,
            'documento_codigo' => $documentoCodigo,
            'tipo' => 'venta',
            'concepto' => 'Venta '.$documentoCodigo.' - Cliente: '.$clienteNombre,
            'empresa_id' => $venta->empresa_id ?? Auth::user()?->empresa_id,
            'sucursal_id' => $venta->sucursal_id ?? Auth::user()?->sucursal_id,
        ]);

        $cuentaClientes = self::obtenerOCrearCuenta('1.1.1', 'Clientes', 'activo', 'deudora');
        $cuentaVentas = self::obtenerOCrearCuenta('4.1', 'Ventas', 'ingreso', 'acreedora');
        $cuentaIva = self::obtenerOCrearCuenta('2.1.3', 'IVA Débito Fiscal', 'pasivo', 'acreedora');
        $cuentaInventario = self::obtenerOCrearCuenta('1.1.5', 'Inventario', 'activo', 'deudora');
        $cuentaCostoVenta = self::obtenerOCrearCuenta('6.1', 'Costo de Ventas', 'costo', 'deudora');

        $asiento->detalles()->create([
            'linea' => 1,
            'cuenta_id' => $cuentaClientes?->id,
            'debe' => $total,
            'haber' => 0,
            'descripcion' => 'Venta '.$documentoCodigo,
        ]);

        $asiento->detalles()->create([
            'linea' => 2,
            'cuenta_id' => $cuentaVentas?->id,
            'debe' => 0,
            'haber' => $subtotal,
            'descripcion' => 'Ingreso por venta '.$documentoCodigo,
        ]);

        if ($impuesto > 0 && $cuentaIva) {
            $asiento->detalles()->create([
                'linea' => 3,
                'cuenta_id' => $cuentaIva->id,
                'debe' => 0,
                'haber' => $impuesto,
                'descripcion' => 'IVA por venta '.$documentoCodigo,
            ]);
        }

        if ($costoTotal > 0 && $cuentaCostoVenta && $cuentaInventario) {
            $asiento->detalles()->create([
                'linea' => 4,
                'cuenta_id' => $cuentaCostoVenta->id,
                'debe' => $costoTotal,
                'haber' => 0,
                'descripcion' => 'Costo de venta '.$documentoCodigo,
            ]);

            $asiento->detalles()->create([
                'linea' => 5,
                'cuenta_id' => $cuentaInventario->id,
                'debe' => 0,
                'haber' => $costoTotal,
                'descripcion' => 'Salida de inventario por venta '.$documentoCodigo,
            ]);
        }

        $asiento->recalcularTotales();
        $asiento->confirmar();

        return $asiento;
    }

    /**
     * Crear asiento desde una compra
     */
    public static function crearDesdeCompra($compra, $fechaContable = null)
    {
        $existente = self::query()
            ->where('documento_tipo', 'compra')
            ->where('documento_id', $compra->id)
            ->first();

        if ($existente) {
            return $existente;
        }

        $compra->loadMissing('recepcion');
        if (! $compra->recepcion?->inventario_procesado_at || $compra->recepcion->estado !== 'completada') {
            throw new \RuntimeException('La factura solo puede contabilizarse como inventario después de que la recepción física esté completada y su ingreso a almacén haya sido procesado.');
        }

        $cuentaInventario = self::obtenerOCrearCuenta('1.1.5', 'Inventario', 'activo', 'deudora');
        $cuentaIva = self::obtenerOCrearCuenta('1.1.4', 'IVA Crédito Fiscal', 'activo', 'deudora');
        $cuentaProveedores = self::obtenerOCrearCuenta('2.1.1', 'Proveedores', 'pasivo', 'acreedora');
        $cuentaAnticipos = self::obtenerOCrearCuenta('1.1.3', 'Anticipos a proveedores', 'activo', 'deudora');

        $asiento = self::create([
            'codigo' => self::generarCodigo(),
            'fecha_asiento' => $fechaContable ?? now(),
            'fecha_contable' => $fechaContable,
            'documento_tipo' => 'compra',
            'documento_id' => $compra->id,
            'documento_codigo' => $compra->codigo,
            'tipo' => 'compra',
            'concepto' => 'Compra '.$compra->codigo.' - Proveedor: '.$compra->proveedor->nombre,
            'empresa_id' => $compra->empresa_id,
        ]);

        // Debe: Inventario
        $asiento->detalles()->create([
            'linea' => 1,
            'cuenta_id' => $cuentaInventario?->id,
            'debe' => $compra->subtotal,
            'haber' => 0,
            'descripcion' => 'Compra '.$compra->codigo,
        ]);

        // Debe: IVA Crédito Fiscal
        if ($compra->impuesto > 0) {
            $asiento->detalles()->create([
                'linea' => 2,
                'cuenta_id' => PlanCuenta::where('codigo', '1.1.4')->first()?->id, // IVA Crédito Fiscal
                'debe' => $compra->impuesto,
                'haber' => 0,
                'descripcion' => 'IVA por compra '.$compra->codigo,
            ]);
        }

        // Haber: Cuenta por Pagar
        $asiento->detalles()->create([
            'linea' => 3,
            'cuenta_id' => $cuentaProveedores?->id,
            'debe' => 0,
            'haber' => $compra->total,
            'descripcion' => 'Compra '.$compra->codigo,
        ]);

        $anticiposAplicados = $compra->pagos()->where('estado', 'confirmado')->get()->sum(function ($pago) use ($cuentaAnticipos) {
            return self::query()
                ->where('documento_tipo', 'pago_proveedor')
                ->where('documento_id', $pago->id)
                ->where('estado', 'confirmado')
                ->whereHas('detalles', fn ($query) => $query->where('cuenta_id', $cuentaAnticipos?->id)->where('haber', '>', 0))
                ->exists() ? (float) $pago->monto : 0;
        });
        if ($anticiposAplicados > 0) {
            $asiento->detalles()->create(['linea' => 4, 'cuenta_id' => $cuentaProveedores?->id, 'debe' => $anticiposAplicados, 'haber' => 0, 'descripcion' => 'Aplicación de anticipos de '.$compra->codigo]);
            $asiento->detalles()->create(['linea' => 5, 'cuenta_id' => $cuentaAnticipos?->id, 'debe' => 0, 'haber' => $anticiposAplicados, 'descripcion' => 'Aplicación de anticipos de '.$compra->codigo]);
        }

        $asiento->recalcularTotales();
        $asiento->confirmar();

        return $asiento;
    }

    public static function crearDesdePagoCliente(Pago $pago): self
    {
        $existente = self::query()->where('documento_tipo', 'pago_cliente')->where('documento_id', $pago->id)->first();
        if ($existente) {
            return $existente;
        }

        $pago->loadMissing('factura');
        $cuentaFondos = match ($pago->tipo_pago) {
            'efectivo' => self::obtenerOCrearCuenta('1.1.2.1', 'Caja general', 'activo', 'deudora'),
            'cheque' => self::obtenerOCrearCuenta('1.1.2.3', 'Cheques por depositar', 'activo', 'deudora'),
            'otros' => self::obtenerOCrearCuenta('1.1.2.9', 'Fondos por identificar', 'activo', 'deudora'),
            'nota_credito' => self::obtenerOCrearCuenta('4.1.9', 'Devoluciones y descuentos sobre ventas', 'ingreso', 'deudora'),
            default => self::obtenerOCrearCuenta('1.1.2.2', 'Bancos y cobros electrónicos', 'activo', 'deudora'),
        };
        $cuentaAnticipos = self::obtenerOCrearCuenta('2.1.2', 'Anticipos de clientes', 'pasivo', 'acreedora');
        $concepto = 'Cobro '.$pago->numero.' de factura '.$pago->factura->numero.' mediante '.$pago->tipo_pago;

        $asiento = self::create([
            'fecha_asiento' => $pago->fecha_pago,
            'fecha_contable' => $pago->fecha_pago,
            'documento_tipo' => 'pago_cliente',
            'documento_id' => $pago->id,
            'documento_codigo' => $pago->numero,
            'tipo' => 'ingreso',
            'concepto' => $concepto,
            'empresa_id' => $pago->empresa_id,
        ]);
        $asiento->detalles()->create(['linea' => 1, 'cuenta_id' => $cuentaFondos->id, 'debe' => $pago->monto, 'haber' => 0, 'descripcion' => $concepto, 'empresa_id' => $pago->empresa_id]);
        $asiento->detalles()->create(['linea' => 2, 'cuenta_id' => $cuentaAnticipos->id, 'debe' => 0, 'haber' => $pago->monto, 'descripcion' => 'Anticipo recibido del cliente', 'empresa_id' => $pago->empresa_id]);
        $asiento->recalcularTotales();
        $asiento->confirmar();

        return $asiento;
    }

    public static function aplicarAnticiposCliente(Factura $factura): ?self
    {
        $existente = self::query()->where('documento_tipo', 'aplicacion_anticipos_cliente')->where('documento_id', $factura->id)->first();
        if ($existente) {
            return $existente;
        }

        $anticipos = (float) $factura->pagos()->where('estado', 'confirmado')->sum('monto');
        if ($anticipos <= 0) {
            return null;
        }

        $fechaAplicacion = $factura->pagos()->where('estado', 'confirmado')->max('fecha_pago') ?? now();

        $cuentaAnticipos = self::obtenerOCrearCuenta('2.1.2', 'Anticipos de clientes', 'pasivo', 'acreedora');
        $cuentaClientes = self::obtenerOCrearCuenta('1.1.1', 'Clientes', 'activo', 'deudora');
        $concepto = 'Aplicación de anticipos a factura '.$factura->numero;
        $asiento = self::create([
            'fecha_asiento' => $fechaAplicacion, 'fecha_contable' => $fechaAplicacion,
            'documento_tipo' => 'aplicacion_anticipos_cliente', 'documento_id' => $factura->id,
            'documento_codigo' => $factura->numero, 'tipo' => 'diario', 'concepto' => $concepto,
            'empresa_id' => $factura->empresa_id,
        ]);
        $asiento->detalles()->create(['linea' => 1, 'cuenta_id' => $cuentaAnticipos->id, 'debe' => $anticipos, 'haber' => 0, 'descripcion' => $concepto, 'empresa_id' => $factura->empresa_id]);
        $asiento->detalles()->create(['linea' => 2, 'cuenta_id' => $cuentaClientes->id, 'debe' => 0, 'haber' => $anticipos, 'descripcion' => $concepto, 'empresa_id' => $factura->empresa_id]);
        $asiento->recalcularTotales();
        $asiento->confirmar();

        return $asiento;
    }

    public static function crearDesdePagoProveedor($pago)
    {
        $existente = self::query()->where('documento_tipo', 'pago_proveedor')->where('documento_id', $pago->id)->first();
        if ($existente) {
            return $existente;
        }

        $pago->loadMissing('factura');
        $compraContabilizada = self::query()->where('documento_tipo', 'compra')->where('documento_id', $pago->factura_id)->where('estado', 'confirmado')->exists();
        $cuentaDestino = $compraContabilizada
            ? self::obtenerOCrearCuenta('2.1.1', 'Proveedores', 'pasivo', 'acreedora')
            : self::obtenerOCrearCuenta('1.1.3', 'Anticipos a proveedores', 'activo', 'deudora');
        $cuentaBanco = self::obtenerOCrearCuenta('1.1.2.2', 'Bancos y cobros electrónicos', 'activo', 'deudora');
        $concepto = ($compraContabilizada ? 'Pago de factura ' : 'Anticipo de factura ').$pago->factura->codigo;

        $asiento = self::create(['codigo' => self::generarCodigo(), 'fecha_asiento' => $pago->fecha_pago, 'documento_tipo' => 'pago_proveedor', 'documento_id' => $pago->id, 'documento_codigo' => $pago->codigo, 'tipo' => 'egreso', 'concepto' => $concepto, 'empresa_id' => $pago->empresa_id]);
        $asiento->detalles()->create(['linea' => 1, 'cuenta_id' => $cuentaDestino?->id, 'debe' => $pago->monto, 'haber' => 0, 'descripcion' => $concepto]);
        $asiento->detalles()->create(['linea' => 2, 'cuenta_id' => $cuentaBanco?->id, 'debe' => 0, 'haber' => $pago->monto, 'descripcion' => 'Salida de fondos '.$pago->codigo]);
        $asiento->recalcularTotales();
        $asiento->confirmar();

        return $asiento;
    }
}
