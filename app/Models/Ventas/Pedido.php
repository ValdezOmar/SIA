<?php

namespace App\Models\Ventas;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\MovimientoInventario;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use App\Models\Ventas\Concerns\ValidaContextoVenta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Pedido extends Model
{
    use SoftDeletes, ValidaContextoVenta;

    protected $table = 'ven_pedidos';

    protected $guarded = [];

    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_entrega_estimada' => 'date',
        'fecha_entrega_real' => 'date',
        'tasa_cambio' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'costo_envio' => 'decimal:2',
        'tasa_impuesto' => 'decimal:2',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Asignar creador
            if (Auth::check()) {
                $model->creado_por = Auth::id();
                $model->empresa_id ??= Auth::user()?->empresa_id;
                $model->sucursal_id ??= Auth::user()?->sucursal_id;
            }

            // Generar código si no tiene
            if (empty($model->codigo)) {
                $model->codigo = self::generarCodigo();
            }
        });

        // USAR SAVED para calcular totales
        static::saved(function ($model) {
            $model->calcularTotalesDesdeDetalles();
        });
    }

    /**
     * Calcular totales desde los detalles
     */
    public function calcularTotalesDesdeDetalles()
    {
        $subtotal = 0;
        $descuento = 0;
        $impuesto = 0;
        $total = 0;

        $this->load('detalles');

        foreach ($this->detalles as $detalle) {
            $subtotal += floatval($detalle->subtotal ?? 0);
            $descuento += floatval($detalle->descuento ?? 0);
            $impuesto += floatval($detalle->impuesto ?? 0);
            $total += floatval($detalle->total ?? 0);
        }

        // Agregar costo de envío al total
        $total += floatval($this->costo_envio ?? 0);

        $this->subtotal = $subtotal;
        $this->descuento = $descuento;
        $this->impuesto = $impuesto;
        $this->total = $total;

        $this->saveQuietly();
    }

    // ========== RELACIONES ==========

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class)->orderBy('linea');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'pedido_id');
    }

    public function pagos()
    {
        return $this->hasManyThrough(Pago::class, Factura::class, 'pedido_id', 'factura_id');
    }

    public function facturaParaEntrega(): ?Factura
    {
        $facturas = $this->facturas()->where('estado', '!=', 'anulada')->get();
        if ($facturas->count() > 1) {
            throw new RuntimeException('El pedido tiene varias facturas activas. Revise sus saldos y asociaciones antes de confirmar la entrega.');
        }

        return $facturas->first();
    }

    public function saldoParaEntrega(): float
    {
        $factura = $this->facturaParaEntrega();

        return $factura ? max(0, round((float) $factura->total - (float) $factura->pagos()->where('estado', 'confirmado')->sum('monto'), 2)) : 0;
    }

    public function confirmarEntrega(?array $datosPago = null): array
    {
        return DB::transaction(function () use ($datosPago): array {
            $pedido = self::query()->lockForUpdate()->findOrFail($this->id);
            if (! in_array($pedido->estado, ['reservado', 'pendiente'], true)) {
                throw new RuntimeException('El pedido ya fue entregado o no está disponible para confirmar entrega. No se registró ningún pago.');
            }
            $factura = $pedido->facturaParaEntrega();
            if (! $factura) {
                throw new RuntimeException('Debe asociar una factura al pedido antes de confirmar la entrega.');
            }
            $factura = Factura::query()->lockForUpdate()->findOrFail($factura->id);
            $factura->actualizarSaldo();
            $saldo = round((float) $factura->saldo, 2);
            $pago = null;
            if ($saldo > 0) {
                if (! $datosPago) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['registrar_pago' => 'Entrega bloqueada: quedan '.number_format($saldo, 2).' '.$factura->moneda.' pendientes. Registre el pago completo antes de entregar.']);
                }
                if (abs((float) ($datosPago['monto'] ?? 0) - $saldo) > 0.005) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['monto' => 'El pago debe cubrir exactamente el saldo actual de '.number_format($saldo, 2).' '.$factura->moneda.'. No se registró el pago ni la entrega.']);
                }
                $pago = $factura->registrarPago($datosPago);
            } else {
                $factura->procesarVentaAutomatica();
            }

            return ['factura' => $factura->fresh(), 'pago' => $pago, 'monto' => $pago ? $saldo : 0];
        });
    }

    public function cancelar(string $motivo): array
    {
        $motivo = trim($motivo);
        if ($motivo === '' || mb_strlen($motivo) > 2000) {
            throw \Illuminate\Validation\ValidationException::withMessages(['motivo' => 'Indique un motivo de cancelación de hasta 2000 caracteres.']);
        }

        return DB::transaction(function () use ($motivo): array {
            $pedido = self::query()->lockForUpdate()->findOrFail($this->id);
            if (! in_array($pedido->estado, ['reservado', 'pendiente', 'parcial'], true)) {
                throw new RuntimeException('El pedido ya fue cancelado o no admite cancelación. No se modificaron facturas ni pagos.');
            }

            $facturas = $pedido->facturas()->where('estado', '!=', 'anulada')->orderBy('id')->lockForUpdate()->get();
            $pagosAnulados = 0;
            $importes = [];
            $motivoAnulacion = 'Cancelación del pedido '.$pedido->codigo.': '.$motivo;
            foreach ($facturas as $factura) {
                $pagos = $factura->pagos()->whereIn('estado', ['pendiente', 'confirmado'])->lockForUpdate()->get();
                $pagosAnulados += $pagos->count();
                foreach ($pagos->where('estado', 'confirmado') as $pago) {
                    $moneda = $pago->moneda ?? $factura->moneda;
                    $importes[$moneda] = ($importes[$moneda] ?? 0) + (float) $pago->monto;
                }
                $factura->anular($motivoAnulacion);
            }

            $pedido->liberarReservaInventario();
            $pedido->update([
                'estado' => 'cancelado',
                'observaciones' => trim(($pedido->observaciones ? $pedido->observaciones."\n" : '').$motivoAnulacion),
            ]);

            return ['facturas' => $facturas->pluck('numero')->all(), 'pagos_anulados' => $pagosAnulados, 'importes_revertidos' => $importes];
        });
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // ========== SCOPES ==========

    public function scopeByEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['reservado', 'pendiente', 'parcial']);
    }

    public function scopeCompletados($query)
    {
        return $query->whereIn('estado', ['despachado', 'entregado']);
    }

    // ========== ACCESORS ==========

    public function getEstadoLabelAttribute()
    {
        return match ($this->estado) {
            'reservado' => 'Reservado',
            'pendiente' => 'Pendiente',
            'parcial' => 'Parcial',
            'despachado' => 'Despachado',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
            default => $this->estado,
        };
    }

    public function getEstadoColorAttribute()
    {
        return match ($this->estado) {
            'reservado' => 'warning',
            'pendiente' => 'info',
            'parcial' => 'primary',
            'despachado' => 'success',
            'entregado' => 'success',
            'cancelado' => 'danger',
            default => 'gray',
        };
    }

    public function getPrioridadLabelAttribute()
    {
        return match ($this->prioridad) {
            'baja' => 'Baja',
            'normal' => 'Normal',
            'alta' => 'Alta',
            'urgente' => 'Urgente',
            default => $this->prioridad,
        };
    }

    public function getPrioridadColorAttribute()
    {
        return match ($this->prioridad) {
            'baja' => 'gray',
            'normal' => 'info',
            'alta' => 'warning',
            'urgente' => 'danger',
            default => 'gray',
        };
    }

    public function getTotalItemsAttribute()
    {
        return $this->detalles()->sum('cantidad');
    }

    public function getEstaCompletadoAttribute()
    {
        return in_array($this->estado, ['despachado', 'entregado']);
    }

    public function getEstaPendienteAttribute()
    {
        return in_array($this->estado, ['reservado', 'pendiente', 'parcial']);
    }

    // ========== MÉTODOS ==========

    public function recalcularTotales()
    {
        $subtotal = 0;
        $descuento = 0;
        $impuesto = 0;

        foreach ($this->detalles as $detalle) {
            $subtotal += $detalle->subtotal;
            $descuento += $detalle->descuento;
            $impuesto += $detalle->impuesto;
        }

        $this->update([
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'impuesto' => $impuesto,
            'total' => $subtotal - $descuento + $impuesto + $this->costo_envio,
        ]);
    }

    public function cambiarEstado($estado)
    {
        $this->update(['estado' => $estado]);

        return $this;
    }

    public function reservarInventario($fechaReserva = null): void
    {
        DB::transaction(function () use ($fechaReserva): void {
            if (MovimientoInventario::query()->where('documento_tipo', 'pedido_reserva')->where('documento_id', $this->id)->where('estado', 'confirmado')->exists()) {
                return;
            }

            if (! $this->detalles()->whereHas('articulo', fn ($query) => $query->where('inventariable', true))->where('cantidad', '>', 0)->exists()) {
                return;
            }

            $almacen = Almacen::query()
                ->where('activo', true)
                ->where('empresa_id', $this->empresa_id)
                ->when($this->sucursal_id, fn ($query) => $query
                    ->where(fn ($almacenes) => $almacenes->where('sucursal_id', $this->sucursal_id)->orWhereNull('sucursal_id'))
                    ->orderByRaw('sucursal_id IS NULL'))
                ->first();
            if (! $almacen) {
                throw new RuntimeException('No existe un almacén activo para reservar los productos del pedido.');
            }

            foreach ($this->detalles as $detalle) {
                $cantidad = (float) ($detalle->cantidad ?? 0);
                if (! $detalle->articulo_id || ! $detalle->articulo?->inventariable || $cantidad <= 0) {
                    continue;
                }
                $existencia = Existencia::query()->where('articulo_id', $detalle->articulo_id)->where('almacen_id', $almacen->id)->lockForUpdate()->first();
                $disponible = (float) ($existencia?->cantidad_disponible ?? 0) - (float) ($existencia?->cantidad_comprometida ?? 0);
                if ((! $existencia || $disponible < $cantidad) && ! $almacen->permite_inventario_negativo) {
                    throw new RuntimeException('No hay stock disponible para reservar el artículo '.($detalle->articulo?->nombre_comercial ?? $detalle->articulo_id).'.');
                }
                $existencia ??= Existencia::create([
                    'articulo_id' => $detalle->articulo_id,
                    'almacen_id' => $almacen->id,
                    'cantidad_disponible' => 0,
                    'cantidad_comprometida' => 0,
                    'costo_promedio' => 0,
                    'costo_acumulado' => 0,
                    'ultimo_costo' => 0,
                ]);
                $existencia->increment('cantidad_comprometida', $cantidad);
                MovimientoInventario::create([
                    'articulo_id' => $detalle->articulo_id, 'almacen_id' => $almacen->id, 'tipo' => 'reserva_pedido', 'cantidad' => $cantidad,
                    'documento_tipo' => 'pedido_reserva', 'documento_id' => $this->id, 'documento_codigo' => $this->codigo,
                    'fecha' => $fechaReserva ?? now(), 'observacion' => 'Reserva de pedido '.$this->codigo, 'estado' => 'confirmado',
                ]);
            }
        });
    }

    public function liberarReservaInventario(): void
    {
        DB::transaction(function (): void {
            MovimientoInventario::query()->where('documento_tipo', 'pedido_reserva')->where('documento_id', $this->id)->where('estado', 'confirmado')->lockForUpdate()->get()
                ->each(function (MovimientoInventario $reserva): void {
                    $cantidad = (float) $reserva->cantidad;
                    if ($cantidad <= 0) {
                        $cantidad = (float) $this->detalles()->where('articulo_id', $reserva->articulo_id)->sum('cantidad');
                    }
                    Existencia::query()->where('articulo_id', $reserva->articulo_id)->where('almacen_id', $reserva->almacen_id)->lockForUpdate()->first()?->decrement('cantidad_comprometida', $cantidad);
                    $reserva->update(['estado' => 'cancelado', 'observacion' => $reserva->observacion.'; reserva liberada']);
                });
        });
    }

    /**
     * Convertir desde una cotización
     */
    public static function crearDesdeCotizacion(Cotizacion $cotizacion, $data = [])
    {
        $pedido = self::create([
            'codigo' => self::generarCodigo(),
            'cotizacion_id' => $cotizacion->id,
            'cliente_id' => $cotizacion->cliente_id,
            'sucursal_id' => $cotizacion->sucursal_id,
            'fecha_pedido' => now(),
            'fecha_entrega_estimada' => $cotizacion->fecha_entrega_estimada,
            'condicion_pago' => $cotizacion->condicion_pago,
            'moneda' => $cotizacion->moneda,
            'tasa_cambio' => $cotizacion->tasa_cambio,
            'observaciones' => $data['observaciones'] ?? $cotizacion->observaciones,
            'vendedor_id' => $cotizacion->vendedor_id,
            'empresa_id' => $cotizacion->empresa_id,
            'estado' => 'pendiente',
            'prioridad' => $data['prioridad'] ?? 'normal',
            'direccion_envio' => $data['direccion_envio'] ?? null,
            'metodo_envio' => $data['metodo_envio'] ?? null,
            'costo_envio' => $data['costo_envio'] ?? 0,
            'instrucciones_especiales' => $data['instrucciones_especiales'] ?? null,
        ]);

        foreach ($cotizacion->detalles as $detalle) {
            $pedido->detalles()->create([
                'linea' => $detalle->linea,
                'articulo_id' => $detalle->articulo_id,
                'codigo_articulo' => $detalle->codigo_articulo,
                'descripcion_articulo' => $detalle->descripcion_articulo,
                'unidad_medida' => $detalle->unidad_medida,
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'precio_original' => $detalle->precio_original,
                'descuento' => $detalle->descuento,
                'descuento_porcentaje' => $detalle->descuento_porcentaje,
                'subtotal' => $detalle->subtotal,
                'tipo_impuesto' => $detalle->tipo_impuesto,
                'tasa_impuesto' => $detalle->tasa_impuesto,
                'impuesto' => $detalle->impuesto,
                'total' => $detalle->total,
                'observaciones' => $detalle->observaciones,
                'tiempo_entrega_dias' => $detalle->tiempo_entrega_dias,
            ]);
        }

        // Actualizar estado de la cotización
        $cotizacion->update(['estado' => 'convertida']);

        // Recalcular totales del pedido
        $pedido->calcularTotalesDesdeDetalles();

        return $pedido;
    }

    /**
     * Generar código único para el pedido
     * Formato: PED-260001 (Año + Correlativo de 4 dígitos)
     */
    public static function generarCodigo()
    {
        $gestion = date('y');
        $prefijo = 'PED-'.$gestion;

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
}
