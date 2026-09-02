<?php

namespace App\Models\Ventas;

use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\MovimientoInventario;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use App\Models\Ventas\Concerns\ValidaContextoVenta;
use App\Services\Inventario\TrazabilidadInventarioService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Factura extends Model
{
    use Prunable, SoftDeletes, ValidaContextoVenta;

    protected $table = 'ven_facturas';  // Nombre correcto

    protected $guarded = [];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'saldo' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'monto_restante' => 'decimal:2',
        'tasa_cambio' => 'decimal:2',
        'tasa_impuesto' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->creado_por = Auth::id();
                $model->empresa_id ??= Auth::user()?->empresa_id;
                $model->sucursal_id ??= Auth::user()?->sucursal_id;
            }
            if (empty($model->numero)) {
                $model->numero = self::generarNumero();
            }
            // Establecer valores por defecto para todos los campos numéricos
            $model->subtotal = $model->subtotal ?? 0;
            $model->descuento = $model->descuento ?? 0;
            $model->impuesto = $model->impuesto ?? 0;
            $model->total = $model->total ?? 0; // ← Agregar esta línea
            $model->saldo = $model->total ?? 0;
            $model->monto_pagado = 0;
            $model->monto_restante = $model->total ?? 0;
        });
    }

    // ========== RELACIONES ==========

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function detalles()
    {
        return $this->hasMany(FacturaDetalle::class, 'factura_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'factura_id');
    }

    public function notasCredito()
    {
        return $this->hasMany(NotaCredito::class, 'factura_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function cobrador()
    {
        return $this->belongsTo(User::class, 'cobrador_id');
    }

    // ========== MÉTODOS ==========

    public function registrarPago($data)
    {
        $monto = (float) ($data['monto'] ?? 0);
        if ($monto <= 0 || $monto > (float) $this->saldo) {
            throw ValidationException::withMessages(['monto' => 'El pago debe ser mayor a cero y no puede superar el saldo pendiente.']);
        }

        $pago = Pago::create([
            'factura_id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'numero' => Pago::generarNumero(),
            'fecha_pago' => $data['fecha_pago'],
            'tipo_pago' => $data['tipo_pago'],
            'monto' => $monto,
            'moneda' => $this->moneda,
            'tasa_cambio' => $this->tasa_cambio,
            'referencia' => $data['referencia'] ?? null,
            'creado_por' => Auth::id(),
            'empresa_id' => $this->empresa_id,
            'estado' => 'confirmado',
        ]);

        $this->actualizarSaldo();
        if ((float) $this->monto_pagado > 0 && (float) $this->saldo > 0) {
            $this->asegurarPedidoReservado();
        }

        return $pago;
    }

    public function crearPagoAutomaticoSiEsContado(): ?Pago
    {
        if (($this->condicion_pago ?? null) !== 'contado') {
            return null;
        }

        $pagoExistente = $this->pagos()
            ->whereIn('estado', ['pendiente', 'confirmado'])
            ->first();

        if ($pagoExistente) {
            return $pagoExistente;
        }

        $totalFactura = (float) ($this->total ?? 0);
        if ($totalFactura <= 0) {
            $totalFactura = (float) $this->detalles()->sum('total');
        }

        if ($totalFactura <= 0) {
            $totalFactura = (float) $this->detalles()->sum('subtotal');
        }

        $fechaPago = $this->fecha_pago ?: now()->toDateString();
        $montoPago = $totalFactura;

        $this->subtotal = $this->subtotal ?? 0;
        $this->descuento = $this->descuento ?? 0;
        $this->impuesto = $this->impuesto ?? 0;
        $this->total = $totalFactura;
        $this->saldo = $totalFactura;
        $this->monto_pagado = 0;
        $this->monto_restante = $totalFactura;
        $this->fecha_pago = $fechaPago;
        $this->fecha_vencimiento = $this->fecha_vencimiento ?: $fechaPago;
        $this->save();

        $pago = Pago::create([
            'factura_id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'numero' => Pago::generarNumero(),
            'fecha_pago' => $fechaPago,
            'tipo_pago' => 'efectivo',
            'monto' => $montoPago,
            'moneda' => $this->moneda ?? 'BOB',
            'tasa_cambio' => $this->tasa_cambio ?? 1,
            'referencia' => 'PAGO AUTOMÁTICO CONTADO',
            'observaciones' => 'Pago generado automáticamente al guardar la factura en condición de contado.',
            'creado_por' => Auth::id(),
            'empresa_id' => $this->empresa_id,
            'estado' => 'confirmado',
        ]);

        $this->actualizarSaldo();

        return $pago;
    }

    public function actualizarSaldo()
    {
        $totalPagado = $this->pagos()->where('estado', 'confirmado')->sum('monto');
        $this->monto_pagado = $totalPagado;
        $this->saldo = ($this->total ?? 0) - $totalPagado;
        $this->monto_restante = $this->saldo;

        if ((float) $this->monto_pagado <= 0) {
            $this->estado = 'borrador';
        } elseif ($this->saldo <= 0) {
            $this->estado = 'pagada';
        } else {
            $this->estado = 'parcial';
        }

        $this->save();
    }

    public function prunable()
    {
        return static::query()->where('estado', 'borrador')->where('created_at', '<=', now()->subDays(30));
    }

    public function asegurarPedidoReservado(): Pedido
    {
        return DB::transaction(function (): Pedido {
            $factura = self::query()->with('pedido')->lockForUpdate()->findOrFail($this->id);
            $pedido = $factura->pedido;
            if (! $pedido) {
                $pedido = Pedido::create([
                    'cliente_id' => $factura->cliente_id, 'fecha_pedido' => now()->toDateString(),
                    'condicion_pago' => $factura->condicion_pago, 'moneda' => $factura->moneda,
                    'tasa_cambio' => $factura->tasa_cambio, 'vendedor_id' => $factura->vendedor_id,
                    'empresa_id' => $factura->empresa_id, 'sucursal_id' => $factura->sucursal_id, 'estado' => 'reservado',
                    'observaciones' => 'Pedido generado desde la factura '.$factura->numero.' por pago recibido.',
                ]);
                foreach ($factura->detalles as $detalle) {
                    $pedido->detalles()->create($detalle->only(['linea', 'articulo_id', 'lista_precio', 'codigo_articulo', 'descripcion_articulo', 'unidad_medida', 'cantidad', 'precio_unitario', 'precio_original', 'descuento', 'descuento_porcentaje', 'subtotal', 'tipo_impuesto', 'tasa_impuesto', 'impuesto', 'total', 'observaciones']));
                }
                $factura->updateQuietly(['pedido_id' => $pedido->id, 'numero_pedido' => $pedido->codigo]);
            }
            $pedido->reservarInventario();

            return $pedido;
        });
    }

    public function reservarInventario(): void
    {
        DB::transaction(function (): void {
            if (MovimientoInventario::query()
                ->where('documento_tipo', 'venta_reserva')
                ->where('documento_id', $this->id)
                ->where('estado', 'confirmado')
                ->exists()) {
                return;
            }

            $almacen = Almacen::where('activo', true)->first();
            if (! $almacen) {
                throw new \RuntimeException('No existe un almacén activo para reservar los productos.');
            }

            foreach ($this->detalles as $detalle) {
                $cantidad = (float) ($detalle->cantidad ?? 0);
                if (! $detalle->articulo_id || $cantidad <= 0) {
                    continue;
                }

                $existencia = Existencia::query()
                    ->where('articulo_id', $detalle->articulo_id)
                    ->where('almacen_id', $almacen->id)
                    ->lockForUpdate()
                    ->first();
                $disponible = (float) ($existencia?->cantidad_disponible ?? 0) - (float) ($existencia?->cantidad_comprometida ?? 0);
                if (! $existencia || $disponible < $cantidad) {
                    throw new \RuntimeException('No hay stock disponible para reservar el artículo '.($detalle->articulo?->nombre_comercial ?? $detalle->articulo_id).'.');
                }

                $existencia->increment('cantidad_comprometida', $cantidad);
                MovimientoInventario::create([
                    'articulo_id' => $detalle->articulo_id, 'almacen_id' => $almacen->id,
                    'tipo' => 'reserva_venta', 'cantidad' => 0, 'documento_tipo' => 'venta_reserva',
                    'documento_id' => $this->id, 'documento_codigo' => $this->numero, 'fecha' => now(),
                    'observacion' => 'Reserva por pago parcial de venta '.$this->numero, 'estado' => 'confirmado',
                ]);
            }
        });
    }

    public function liberarReservaInventario(): void
    {
        DB::transaction(function (): void {
            $reservas = MovimientoInventario::query()
                ->where('documento_tipo', 'venta_reserva')
                ->where('documento_id', $this->id)
                ->where('estado', 'confirmado')
                ->lockForUpdate()
                ->get();
            foreach ($reservas as $reserva) {
                $cantidad = (float) ($this->detalles()->where('articulo_id', $reserva->articulo_id)->value('cantidad') ?? 0);
                Existencia::query()->where('articulo_id', $reserva->articulo_id)->where('almacen_id', $reserva->almacen_id)
                    ->lockForUpdate()->first()?->decrement('cantidad_comprometida', $cantidad);
                $reserva->update(['estado' => 'cancelado', 'observacion' => $reserva->observacion.'; reserva liberada']);
            }
        });
    }

    public function procesarVentaAutomatica(): array
    {
        $this->refresh();

        if ((float) $this->saldo > 0) {
            throw new \RuntimeException('La entrega solo puede confirmarse cuando el pago total de la venta esté verificado.');
        }

        $this->asegurarPedidoReservado();

        $this->liberarReservaInventario();
        $this->loadMissing('pedido');
        $this->pedido?->liberarReservaInventario();

        $yaExisteKardex = Kardex::where('documento_tipo', 'venta')
            ->where('documento_id', $this->id)
            ->exists();

        $yaExisteAsiento = AsientoContable::where('documento_tipo', 'venta')
            ->where('documento_id', $this->id)
            ->exists();

        $almacen = Almacen::where('activo', true)->first();

        if (! $almacen) {
            throw new \RuntimeException('No existe un almacén activo para registrar la salida de inventario.');
        }

        if (! $yaExisteKardex) {
            foreach ($this->detalles as $detalle) {
                if (! ($detalle->articulo_id ?? null) || ! ($detalle->cantidad ?? 0)) {
                    continue;
                }

                $cantidad = (float) $detalle->cantidad;
                $existencia = Existencia::where('articulo_id', $detalle->articulo_id)
                    ->where('almacen_id', $almacen->id)
                    ->first();

                if (! $existencia) {
                    throw new \RuntimeException('No existe stock del artículo '.($detalle->articulo?->nombre_comercial ?? $detalle->articulo_id).' en el almacén activo.');
                }

                if ((float) $existencia->cantidad_disponible < $cantidad) {
                    throw new \RuntimeException('Stock insuficiente para el artículo '.($detalle->articulo?->nombre_comercial ?? $detalle->articulo_id).'.');
                }

                $kardex = Kardex::registrarSalida([
                    'articulo_id' => $detalle->articulo_id,
                    'almacen_id' => $almacen->id,
                    'tipo_movimiento' => 'venta',
                    'cantidad' => $cantidad,
                    'documento_tipo' => 'venta',
                    'documento_id' => $this->id,
                    'documento_codigo' => $this->numero,
                    'documento_detalle_id' => $detalle->id,
                    // Kardex aplica FIFO: consume automáticamente las capas más antiguas disponibles.
                    'capa_costo_id' => null,
                    'observaciones' => 'Salida por venta '.$this->numero,
                    'empresa_id' => $this->empresa_id ?? Auth::user()?->empresa_id,
                    'fecha_movimiento' => now(),
                    'estado' => 'confirmado',
                ]);

                $movimiento = MovimientoInventario::create([
                    'articulo_id' => $detalle->articulo_id,
                    'almacen_id' => $almacen->id,
                    'tipo' => 'salida_venta',
                    'cantidad' => -$cantidad,
                    'costo_unitario' => $kardex->costo_unitario ?? 0,
                    'costo_total' => $kardex->costo_total ?? 0,
                    'documento_tipo' => 'venta',
                    'documento_id' => $this->id,
                    'documento_codigo' => $this->numero,
                    'fecha' => now(),
                    'observacion' => 'Salida por venta '.$this->numero,
                    'estado' => 'confirmado',
                    'kardex_id' => $kardex->id,
                ]);

                app(TrazabilidadInventarioService::class)->registrarSalida($movimiento, $detalle->articulo, [
                    'cantidad' => $cantidad,
                    'series' => $detalle->series,
                    'lotes' => $detalle->lotes,
                    'cliente_id' => $this->cliente_id,
                ]);
            }
        }

        if (! $yaExisteAsiento) {
            $asiento = AsientoContable::crearDesdeVenta($this);
        } else {
            $asiento = AsientoContable::where('documento_tipo', 'venta')->where('documento_id', $this->id)->first();
        }

        if ($this->pedido && $this->pedido->estado !== 'cancelado') {
            $this->pedido->update([
                'estado' => 'entregado',
                'fecha_entrega_real' => now()->toDateString(),
            ]);
        }
        $this->actualizarSaldo();

        return [
            'kardex' => Kardex::where('documento_tipo', 'venta')->where('documento_id', $this->id)->get(),
            'asiento' => $asiento,
        ];
    }

    public function anular(?string $motivo = null): self
    {
        return DB::transaction(function () use ($motivo) {
            $factura = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($factura->estado === 'anulada') {
                return $factura;
            }

            $factura->liberarReservaInventario();
            $factura->loadMissing('pedido');
            if ($factura->pedido && $factura->pedido->estado !== 'entregado') {
                $factura->pedido->liberarReservaInventario();
                $factura->pedido->update(['estado' => 'cancelado']);
            }

            $salidas = Kardex::where('documento_tipo', 'venta')
                ->where('documento_id', $factura->id)
                ->where('direccion', 'salida')
                ->where('estado', 'confirmado')
                ->lockForUpdate()
                ->get();

            foreach ($salidas as $salida) {
                $salida->revertirSalida($motivo);
                $original = MovimientoInventario::where('kardex_id', $salida->id)->first();
                $reversion = MovimientoInventario::where('kardex_id', $salida->fresh()->movimientoRelacionado?->id)->first();
                if ($original && $reversion) {
                    app(TrazabilidadInventarioService::class)->revertirSalida($original, $reversion);
                }
            }

            MovimientoInventario::where('documento_tipo', 'venta')
                ->where('documento_id', $factura->id)
                ->where('estado', 'confirmado')
                ->update(['estado' => 'cancelado']);

            $factura->pagos()
                ->whereIn('estado', ['pendiente', 'confirmado'])
                ->update(['estado' => 'anulado']);

            $asiento = AsientoContable::where('documento_tipo', 'venta')
                ->where('documento_id', $factura->id)
                ->where('estado', 'confirmado')
                ->first();

            if ($asiento) {
                $asiento->anular($motivo);
            }

            $factura->monto_pagado = 0;
            $factura->saldo = $factura->total;
            $factura->monto_restante = $factura->total;
            $factura->estado = 'anulada';
            $factura->save();

            return $factura;
        });
    }

    public static function generarNumero()
    {
        $gestion = date('y');
        $prefijo = 'FAC-'.$gestion;

        $ultimo = self::withTrashed()
            ->where('numero', 'LIKE', $prefijo.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->numero, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo.str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }
}
