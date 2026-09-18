<?php

namespace App\Models\Compras;

use App\Models\Contabilidad\AsientoContable;
use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacturaCompra extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_facturas_compra';

    protected $guarded = [];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
        'subtotal' => 'decimal:6',
        'descuento' => 'decimal:6',
        'impuesto' => 'decimal:6',
        'total' => 'decimal:6',
        'saldo' => 'decimal:6',
        'monto_pagado' => 'decimal:6',
        'tasa_cambio' => 'decimal:6',
        'pago_pendiente' => 'boolean',
        'adjuntos' => 'array',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->creado_por = Auth::id();
            }
            if (empty($model->codigo)) {
                $model->codigo = self::generarCodigo();
            }
            $model->saldo = $model->total ?? 0;
            $model->monto_pagado = 0;
        });

        static::saving(function ($model): void {
            $model->moneda = strtoupper($model->moneda ?: 'BOB');
            $model->tasa_cambio = $model->moneda === 'BOB'
                ? 1
                : max(0.000001, (float) $model->tasa_cambio);
        });

        static::saved(function ($model) {
            $model->actualizarEstado();
        });
    }

    // ========== RELACIONES ==========

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function recepcion()
    {
        return $this->belongsTo(Recepcion::class);
    }

    public function detalles()
    {
        return $this->hasMany(FacturaCompraDetalle::class, 'factura_id')->orderBy('linea');
    }

    public function pagos()
    {
        return $this->hasMany(PagoProveedor::class, 'factura_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // ========== SCOPES ==========

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['registrada', 'parcial']);
    }

    public function scopePorProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    // ========== ACCESORS ==========

    public function getEstadoLabelAttribute()
    {
        return match ($this->estado) {
            'borrador' => 'Borrador',
            'registrada' => 'Registrada',
            'pagada' => 'Pagada',
            'parcial' => 'Parcial',
            'anulada' => 'Anulada',
            default => $this->estado,
        };
    }

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $gestion = date('y');
        $prefijo = 'FAC-'.$gestion;

        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', $prefijo.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->codigo, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo.str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }

    public function registrarPago(array $data): PagoProveedor
    {
        return DB::transaction(function () use ($data): PagoProveedor {
            /** @var self $factura */
            $factura = self::query()->lockForUpdate()->findOrFail($this->id);

            if (in_array($factura->estado, ['pagada', 'anulada'], true)) {
                throw ValidationException::withMessages(['factura_id' => 'No se pueden registrar pagos para una factura pagada o anulada.']);
            }

            $monto = (float) ($data['monto'] ?? 0);
            if ($monto <= 0 || $monto > (float) $factura->saldo) {
                throw ValidationException::withMessages(['monto' => 'El pago debe ser mayor a cero y no puede superar el saldo pendiente.']);
            }
            $automatico = (bool) ($data['automatico'] ?? false);
            if (! $automatico && (empty($data['respaldos']) || ! is_array($data['respaldos']))) {
                throw ValidationException::withMessages(['respaldos' => 'Adjunte al menos un respaldo del pago.']);
            }

            $pago = PagoProveedor::create([
                'factura_id' => $factura->id,
                'proveedor_id' => $factura->proveedor_id,
                'fecha_pago' => $data['fecha_pago'] ?? now()->toDateString(),
                'tipo_pago' => $data['tipo_pago'] ?? 'efectivo',
                'monto' => $monto,
                'moneda' => $factura->moneda,
                'tasa_cambio' => $factura->tasa_cambio,
                'referencia' => $data['referencia'] ?? null,
                'banco' => $data['banco'] ?? null,
                'numero_cheque' => $data['numero_cheque'] ?? null,
                'fecha_cheque' => $data['fecha_cheque'] ?? null,
                // Un array vacío del formulario no es un respaldo válido. Para el pago
                // automático se registra la trazabilidad interna exigida por el modelo.
                'respaldos' => filled($data['respaldos'] ?? null)
                    ? $data['respaldos']
                    : ($automatico ? ['sistema/registro-automatico'] : []),
                'observaciones' => $data['observaciones'] ?? null,
                'creado_por' => Auth::id(),
                'empresa_id' => $factura->empresa_id,
                'estado' => 'confirmado',
            ]);

            AsientoContable::crearDesdePagoProveedor($pago);

            $factura->actualizarSaldo();

            return $pago;
        });
    }

    /**
     * Prepara la recepción física sin alterar existencias. La entrada a almacén
     * solo ocurre cuando el responsable procesa la recepción.
     */
    public function prepararRecepcionPendiente(): Recepcion
    {
        return DB::transaction(function (): Recepcion {
            /** @var self $factura */
            $factura = self::query()->with('detalles.articulo')->lockForUpdate()->findOrFail($this->id);

            if ($factura->recepcion_id && ($recepcion = $factura->recepcion()->first())) {
                return $recepcion;
            }

            $orden = $factura->ordenCompra;
            if (! $orden) {
                $orden = OrdenCompra::create([
                    'codigo' => OrdenCompra::generarCodigo(),
                    'proveedor_id' => $factura->proveedor_id,
                    'fecha_orden' => $factura->fecha_emision ?? now()->toDateString(),
                    'fecha_entrega_estimada' => $factura->fecha_emision ?? now()->toDateString(),
                    'estado' => 'confirmada',
                    'moneda' => $factura->moneda ?? 'BOB',
                    'tasa_cambio' => $factura->tasa_cambio ?? 1,
                    'condicion_pago' => $factura->condicion_pago,
                    'subtotal' => $factura->subtotal,
                    'descuento' => $factura->descuento,
                    'impuesto' => $factura->impuesto,
                    'total' => $factura->total,
                    'observaciones' => 'Orden generada desde factura de compra '.$factura->codigo,
                    'creado_por' => $factura->creado_por,
                    'empresa_id' => $factura->empresa_id,
                ]);

                foreach ($factura->detalles as $detalle) {
                    $orden->detalles()->create([
                        'articulo_id' => $detalle->articulo_id,
                        'codigo_articulo' => $detalle->codigo_articulo,
                        'descripcion_articulo' => $detalle->descripcion_articulo,
                        'unidad_medida' => $detalle->unidad_medida,
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => $detalle->precio_unitario,
                        'descuento' => $detalle->descuento,
                        'subtotal' => $detalle->subtotal,
                        'impuesto' => $detalle->impuesto,
                        'total' => $detalle->total,
                        'observaciones' => $detalle->observaciones,
                    ]);
                }

                $factura->updateQuietly(['orden_compra_id' => $orden->id]);
            }

            $recepcion = Recepcion::create([
                'codigo' => Recepcion::generarCodigo(),
                'orden_compra_id' => $orden->id,
                'proveedor_id' => $factura->proveedor_id,
                'fecha_recepcion' => $factura->fecha_emision ?? now()->toDateString(),
                'moneda' => $factura->moneda ?? 'BOB',
                'tasa_cambio' => $factura->tasa_cambio ?? 1,
                // SQLite de pruebas conserva el CHECK original; MySQL incorpora
                // el estado listo mediante la migración de este flujo.
                'estado' => $factura->estado === 'pagada'
                    ? (DB::connection()->getDriverName() === 'sqlite' ? 'pendiente' : 'listo')
                    : ($factura->estado === 'parcial' ? 'parcial' : 'pendiente'),
                'observaciones' => 'Pendiente de ingreso físico. Factura '.$factura->codigo.' · pago '.$factura->estado.'.',
                'creado_por' => $factura->creado_por,
                'empresa_id' => $factura->empresa_id,
            ]);

            foreach ($factura->detalles as $detalle) {
                $ordenDetalle = $orden->detalles()->where('articulo_id', $detalle->articulo_id)->orderBy('linea')->firstOrFail();
                $recepcion->detalles()->create([
                    'orden_detalle_id' => $ordenDetalle->id,
                    'articulo_id' => $detalle->articulo_id,
                    'codigo_articulo' => $detalle->codigo_articulo,
                    'descripcion_articulo' => $detalle->descripcion_articulo,
                    'unidad_medida' => $detalle->unidad_medida,
                    'cantidad' => $detalle->cantidad,
                    'cantidad_aceptada' => 0,
                    'cantidad_rechazada' => 0,
                    'costo_unitario' => $detalle->precio_unitario,
                    'costo_total' => 0,
                    'observaciones' => 'Pendiente de verificación física.',
                ]);
            }

            $factura->updateQuietly(['recepcion_id' => $recepcion->id]);

            return $recepcion;
        });
    }

    public function actualizarSaldo()
    {
        $totalPagado = $this->pagos()->where('estado', 'confirmado')->sum('monto');
        $saldo = max(0, (float) $this->total - (float) $totalPagado);
        $this->monto_pagado = $totalPagado;
        $this->saldo = $saldo;
        $this->pago_pendiente = $totalPagado > 0 && $saldo > 0;
        $this->save();
    }

    private function actualizarEstado()
    {
        if (in_array($this->estado, ['borrador', 'anulada'], true) || (float) $this->total <= 0) {
            return;
        }

        if ($this->saldo <= 0 && $this->monto_pagado > 0) {
            $this->estado = 'pagada';
            $this->pago_pendiente = false;
        } elseif ($this->monto_pagado > 0 && $this->saldo > 0) {
            $this->estado = 'parcial';
            $this->pago_pendiente = true;
        } else {
            $this->estado = 'registrada';
            $this->pago_pendiente = false;
        }

        $this->saveQuietly();
    }

    public function anularDocumento(string $motivo): void
    {
        DB::transaction(function () use ($motivo): void {
            /** @var self $factura */
            $factura = self::query()->lockForUpdate()->findOrFail($this->id);
            if ($factura->estado === 'anulada') {
                return;
            }

            $factura->loadMissing('recepcion');
            if ($factura->recepcion?->inventario_procesado_at) {
                throw ValidationException::withMessages([
                    'recepcion_id' => 'No se puede anular una factura cuya recepción ya ingresó inventario. Primero revierta la recepción mediante el proceso de inventario autorizado.',
                ]);
            }

            $factura->pagos()->where('estado', 'confirmado')->each(function (PagoProveedor $pago) use ($motivo): void {
                AsientoContable::query()->where('documento_tipo', 'pago_proveedor')->where('documento_id', $pago->id)->where('estado', 'confirmado')
                    ->each(fn (AsientoContable $asiento) => $asiento->anular($motivo));
                $pago->estado = 'anulado';
                $pago->observaciones = trim(($pago->observaciones ? $pago->observaciones."\n" : '').'Anulado por factura: '.$motivo);
                $pago->saveQuietly();
            });

            AsientoContable::query()
                ->where('documento_tipo', 'compra')
                ->where('documento_id', $factura->id)
                ->where('estado', 'confirmado')
                ->each(fn (AsientoContable $asiento) => $asiento->anular($motivo));

            $factura->updateQuietly([
                'estado' => 'anulada',
                'monto_pagado' => 0,
                'saldo' => 0,
                'pago_pendiente' => false,
                'observaciones' => trim(($factura->observaciones ? $factura->observaciones."\n" : '').'Factura anulada: '.$motivo),
            ]);
        });
    }

    public function recalcularTotales()
    {
        $subtotal = 0;
        $descuento = 0;
        $impuesto = 0;
        $total = 0;

        foreach ($this->detalles as $detalle) {
            $subtotal += floatval($detalle->subtotal ?? 0);
            $descuento += floatval($detalle->descuento ?? 0);
            $impuesto += floatval($detalle->impuesto ?? 0);
            $total += floatval($detalle->total ?? 0);
        }

        $this->update([
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'impuesto' => $impuesto,
            'total' => $total,
        ]);
    }
}
