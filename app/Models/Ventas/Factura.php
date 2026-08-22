<?php

namespace App\Models\Ventas;

use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\MovimientoInventario;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Factura extends Model
{
    use SoftDeletes;

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
        $pago = Pago::create([
            'factura_id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'numero' => Pago::generarNumero(),
            'fecha_pago' => $data['fecha_pago'],
            'tipo_pago' => $data['tipo_pago'],
            'monto' => $data['monto'],
            'moneda' => $this->moneda,
            'tasa_cambio' => $this->tasa_cambio,
            'referencia' => $data['referencia'] ?? null,
            'creado_por' => Auth::id(),
            'empresa_id' => $this->empresa_id,
            'estado' => 'confirmado',
        ]);

        $this->actualizarSaldo();
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

        if ($this->saldo <= 0) {
            $this->estado = 'pagada';
        } elseif ($this->monto_pagado > 0 && $this->saldo > 0) {
            $this->estado = 'parcial';
        } elseif (($this->total ?? 0) > 0) {
            $this->estado = 'emitida';
        } else {
            $this->estado = 'borrador';
        }

        $this->save();
    }

    public function procesarVentaAutomatica(): array
    {
        $this->refresh();

        $yaExisteKardex = Kardex::where('documento_tipo', 'venta')
            ->where('documento_id', $this->id)
            ->exists();

        $yaExisteAsiento = AsientoContable::where('documento_tipo', 'venta')
            ->where('documento_id', $this->id)
            ->exists();

        $almacen = Almacen::where('activo', true)->first();

        if (!$almacen) {
            throw new \RuntimeException('No existe un almacén activo para registrar la salida de inventario.');
        }

        if (!$yaExisteKardex) {
            foreach ($this->detalles as $detalle) {
                if (!($detalle->articulo_id ?? null) || !($detalle->cantidad ?? 0)) {
                    continue;
                }

                $cantidad = (float) $detalle->cantidad;
                $existencia = Existencia::where('articulo_id', $detalle->articulo_id)
                    ->where('almacen_id', $almacen->id)
                    ->first();

                if (!$existencia) {
                    throw new \RuntimeException('No existe stock del artículo ' . ($detalle->articulo?->nombre_comercial ?? $detalle->articulo_id) . ' en el almacén activo.');
                }

                if ((float) $existencia->cantidad_disponible < $cantidad) {
                    throw new \RuntimeException('Stock insuficiente para el artículo ' . ($detalle->articulo?->nombre_comercial ?? $detalle->articulo_id) . '.');
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
                    'observaciones' => 'Salida por venta ' . $this->numero,
                    'empresa_id' => $this->empresa_id ?? Auth::user()?->empresa_id,
                    'fecha_movimiento' => now(),
                    'estado' => 'confirmado',
                ]);

                MovimientoInventario::create([
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
                    'observacion' => 'Salida por venta ' . $this->numero,
                    'estado' => 'confirmado',
                    'kardex_id' => $kardex->id,
                ]);
            }
        }

        if (!$yaExisteAsiento) {
            $asiento = AsientoContable::crearDesdeVenta($this);
        } else {
            $asiento = AsientoContable::where('documento_tipo', 'venta')->where('documento_id', $this->id)->first();
        }

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

            $salidas = Kardex::where('documento_tipo', 'venta')
                ->where('documento_id', $factura->id)
                ->where('direccion', 'salida')
                ->where('estado', 'confirmado')
                ->lockForUpdate()
                ->get();

            foreach ($salidas as $salida) {
                $salida->revertirSalida($motivo);
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
        $prefijo = 'FAC-' . $gestion;

        $ultimo = self::withTrashed()
            ->where('numero', 'LIKE', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->numero, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }
}
