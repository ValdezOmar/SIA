<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kardex extends Model
{
    use SoftDeletes;

    protected $table = 'alm_kardex';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'cantidad_anterior' => 'decimal:6',
        'cantidad_posterior' => 'decimal:6',
        'costo_unitario' => 'decimal:6',
        'costo_total' => 'decimal:6',
        'costo_promedio' => 'decimal:6',
        'costo_acumulado' => 'decimal:6',
        'fecha_movimiento' => 'datetime',
        'fecha_contable' => 'datetime',
        'capas_fifo_consumidas' => 'array',
        'series' => 'array',
        'lotes' => 'array',
        'datos_adicionales' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $kardex) {
            if (empty($kardex->documento_tipo)) {
                $kardex->documento_tipo = 'manual';
            }

            if (empty($kardex->documento_id) && !isset($kardex->documento_id)) {
                $kardex->documento_id = 0;
            }

            if (empty($kardex->usuario_id)) {
                $kardex->usuario_id = auth()->id();
            }

            if (empty($kardex->creado_por)) {
                $kardex->creado_por = auth()->id();
            }

            if (empty($kardex->empresa_id)) {
                $kardex->empresa_id = auth()->user()?->empresa_id;
            }
        });
    }

    // ========== RELACIONES ==========

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function autorizador()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function movimientoRelacionado()
    {
        return $this->belongsTo(self::class, 'movimiento_relacionado_id');
    }

    public function movimientoInventario()
    {
        return $this->hasOne(MovimientoInventario::class);
    }

    public function capaCosto()
    {
        return $this->hasOne(CapaCosto::class);
    }

    // ========== SCOPES ==========

    public function scopeEntradas($query)
    {
        return $query->where('direccion', 'entrada');
    }

    public function scopeSalidas($query)
    {
        return $query->where('direccion', 'salida');
    }

    public function scopePorArticulo($query, $articuloId)
    {
        return $query->where('articulo_id', $articuloId);
    }

    public function scopePorAlmacen($query, $almacenId)
    {
        return $query->where('almacen_id', $almacenId);
    }

    public function scopePorPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin]);
    }

    public function scopeConfirmados($query)
    {
        return $query->where('estado', 'confirmado');
    }

    // ========== ACCESORS ==========

    public function getTipoMovimientoLabelAttribute()
    {
        $tipos = [
            'compra' => 'Compra',
            'venta' => 'Venta',
            'transferencia_entrada' => 'Transferencia Entrada',
            'transferencia_salida' => 'Transferencia Salida',
            'ajuste_incremento' => 'Ajuste (+)',
            'ajuste_decremento' => 'Ajuste (-)',
            'devolucion_compra' => 'Devolución Compra',
            'devolucion_venta' => 'Devolución Venta',
            'produccion_entrada' => 'Producción Entrada',
            'produccion_salida' => 'Producción Salida',
            'inventario_inicial' => 'Inventario Inicial',
            'ajuste_fisico' => 'Ajuste Físico',
            'merma' => 'Merma',
            'despacho' => 'Despacho',
            'consignacion' => 'Consignación',
        ];

        return $tipos[$this->tipo_movimiento] ?? $this->tipo_movimiento;
    }

    public function getDireccionLabelAttribute()
    {
        return $this->direccion === 'entrada' ? 'Entrada' : 'Salida';
    }

    public function getDireccionColorAttribute()
    {
        return $this->direccion === 'entrada' ? 'success' : 'danger';
    }

    public function getEstadoLabelAttribute()
    {
        return match($this->estado) {
            'pendiente' => 'Pendiente',
            'confirmado' => 'Confirmado',
            'cancelado' => 'Cancelado',
            'anulado' => 'Anulado',
            default => $this->estado,
        };
    }

    // ========== MÉTODOS ==========

    /**
     * Registrar un movimiento de entrada
     */
    public static function registrarEntrada($data)
    {
        $articulo = Articulo::find($data['articulo_id']);
        $existencia = Existencia::where('articulo_id', $data['articulo_id'])
            ->where('almacen_id', $data['almacen_id'])
            ->first();

        $cantidadAnterior = $existencia ? $existencia->cantidad_disponible : 0;
        $costoTotal = $data['cantidad'] * $data['costo_unitario'];
        $cantidadPosterior = $cantidadAnterior + $data['cantidad'];

        // Crear registro en kardex
        $kardex = self::create([
            'articulo_id' => $data['articulo_id'],
            'almacen_id' => $data['almacen_id'],
            'ubicacion_id' => $data['ubicacion_id'] ?? null,
            'tipo_movimiento' => $data['tipo_movimiento'],
            'direccion' => 'entrada',
            'cantidad' => $data['cantidad'],
            'cantidad_anterior' => $cantidadAnterior,
            'cantidad_posterior' => $cantidadPosterior,
            'costo_unitario' => $data['costo_unitario'],
            'costo_total' => $costoTotal,
            'costo_promedio' => $cantidadPosterior > 0 ? 
                (($existencia?->costo_promedio ?? 0) * $cantidadAnterior + $costoTotal) / $cantidadPosterior : 
                $data['costo_unitario'],
            'costo_acumulado' => ($existencia?->costo_acumulado ?? 0) + $costoTotal,
            'documento_tipo' => $data['documento_tipo'],
            'documento_id' => $data['documento_id'],
            'documento_codigo' => $data['documento_codigo'] ?? null,
            'usuario_id' => auth()->id(),
            'fecha_movimiento' => $data['fecha_movimiento'] ?? now(),
            'observaciones' => $data['observaciones'] ?? null,
            'estado' => $data['estado'] ?? 'confirmado',
            'empresa_id' => $data['empresa_id'] ?? auth()->user()?->empresa_id,
        ]);

        // Actualizar existencias
        if ($existencia) {
            $existencia->cantidad_disponible = $cantidadPosterior;
            $existencia->costo_promedio = $kardex->costo_promedio;
            $existencia->costo_acumulado = $kardex->costo_acumulado;
            $existencia->ultimo_costo = $data['costo_unitario'];
            $existencia->ultima_entrada = now();
            $existencia->save();
        } else {
            // Crear existencia si no existe
            Existencia::create([
                'articulo_id' => $data['articulo_id'],
                'almacen_id' => $data['almacen_id'],
                'cantidad_disponible' => $cantidadPosterior,
                'costo_promedio' => $kardex->costo_promedio,
                'costo_acumulado' => $kardex->costo_acumulado,
                'ultimo_costo' => $data['costo_unitario'],
            ]);
        }

        // Si es entrada por compra, crear capa FIFO
        if (in_array($data['tipo_movimiento'], ['compra', 'devolucion_venta'], true)) {
            $capa = CapaCosto::create([
                'articulo_id' => $data['articulo_id'],
                'almacen_id' => $data['almacen_id'],
                'kardex_id' => $kardex->id,
                'cantidad_original' => $data['cantidad'],
                'cantidad_disponible' => $data['cantidad'],
                'costo_unitario' => $data['costo_unitario'],
                'fecha' => $data['fecha_movimiento'] ?? now(),
            ]);
            
            $kardex->capa_fifo_id = $capa->id;
            $kardex->save();
        }

        return $kardex;
    }

    public function revertirSalida(?string $motivo = null): ?self
    {
        if ($this->direccion !== 'salida' || $this->estado !== 'confirmado') {
            return null;
        }

        $devolucionExistente = self::where('tipo_movimiento', 'devolucion_venta')
            ->where('documento_tipo', 'devolucion_venta')
            ->where('documento_id', $this->documento_id)
            ->where('documento_detalle_id', $this->documento_detalle_id)
            ->where('estado', 'confirmado')
            ->first();

        if ($devolucionExistente) {
            $this->update(['estado' => 'anulado']);
            return $devolucionExistente;
        }

        $cantidad = (float) $this->cantidad;
        $costoUnitario = $cantidad > 0 ? (float) $this->costo_total / $cantidad : 0;

        $devolucion = self::registrarEntrada([
            'articulo_id' => $this->articulo_id,
            'almacen_id' => $this->almacen_id,
            'ubicacion_id' => $this->ubicacion_id,
            'tipo_movimiento' => 'devolucion_venta',
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'documento_tipo' => 'devolucion_venta',
            'documento_id' => $this->documento_id,
            'documento_codigo' => $this->documento_codigo,
            'documento_detalle_id' => $this->documento_detalle_id,
            'observaciones' => 'Reversión de salida por anulación de venta ' . $this->documento_codigo . ($motivo ? '. Motivo: ' . $motivo : ''),
            'empresa_id' => $this->empresa_id,
            'fecha_movimiento' => now(),
            'estado' => 'confirmado',
        ]);

        MovimientoInventario::create([
            'articulo_id' => $this->articulo_id,
            'almacen_id' => $this->almacen_id,
            'tipo' => 'entrada_devolucion',
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $this->costo_total,
            'documento_tipo' => 'devolucion_venta',
            'documento_id' => $this->documento_id,
            'documento_codigo' => $this->documento_codigo,
            'fecha' => now(),
            'observacion' => 'Entrada por anulación de venta ' . $this->documento_codigo,
            'estado' => 'confirmado',
            'kardex_id' => $devolucion->id,
        ]);

        $this->update(['estado' => 'anulado']);

        return $devolucion;
    }

    /**
     * Registrar un movimiento de salida
     */
    public static function registrarSalida($data)
    {
        $articulo = Articulo::find($data['articulo_id']);
        $existencia = Existencia::where('articulo_id', $data['articulo_id'])
            ->where('almacen_id', $data['almacen_id'])
            ->first();

        if (!$existencia || $existencia->cantidad_disponible < $data['cantidad']) {
            throw new \Exception('Stock insuficiente para la salida');
        }

        $cantidadAnterior = $existencia->cantidad_disponible;
        $cantidadPosterior = $cantidadAnterior - $data['cantidad'];

        // Calcular costo usando FIFO
        $costoTotal = 0;
        $capasConsumidas = [];
        $cantidadPendiente = $data['cantidad'];

        $capas = CapaCosto::where('articulo_id', $data['articulo_id'])
            ->where('almacen_id', $data['almacen_id'])
            ->where('cantidad_disponible', '>', 0)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        foreach ($capas as $capa) {
            if ($cantidadPendiente <= 0) break;

            $cantidadConsumir = min($cantidadPendiente, $capa->cantidad_disponible);
            $costoTotal += $cantidadConsumir * $capa->costo_unitario;

            $capasConsumidas[] = [
                'capa_id' => $capa->id,
                'cantidad' => $cantidadConsumir,
                'costo_unitario' => $capa->costo_unitario,
            ];

            $capa->cantidad_disponible -= $cantidadConsumir;
            $capa->save();

            $cantidadPendiente -= $cantidadConsumir;
        }

        $costoPromedioSalida = $data['cantidad'] > 0 ? $costoTotal / $data['cantidad'] : 0;
        $nuevoCostoPromedio = $cantidadPosterior > 0 ? 
            ($existencia->costo_acumulado - $costoTotal) / $cantidadPosterior : 
            0;

        // Crear registro en kardex
        $kardex = self::create([
            'articulo_id' => $data['articulo_id'],
            'almacen_id' => $data['almacen_id'],
            'ubicacion_id' => $data['ubicacion_id'] ?? null,
            'tipo_movimiento' => $data['tipo_movimiento'],
            'direccion' => 'salida',
            'cantidad' => $data['cantidad'],
            'cantidad_anterior' => $cantidadAnterior,
            'cantidad_posterior' => $cantidadPosterior,
            'costo_unitario' => $costoPromedioSalida,
            'costo_total' => $costoTotal,
            'costo_promedio' => $nuevoCostoPromedio,
            'costo_acumulado' => $existencia->costo_acumulado - $costoTotal,
            'capas_fifo_consumidas' => $capasConsumidas,
            'documento_tipo' => $data['documento_tipo'],
            'documento_id' => $data['documento_id'],
            'documento_codigo' => $data['documento_codigo'] ?? null,
            'usuario_id' => auth()->id(),
            'fecha_movimiento' => $data['fecha_movimiento'] ?? now(),
            'observaciones' => $data['observaciones'] ?? null,
            'estado' => $data['estado'] ?? 'confirmado',
            'empresa_id' => $data['empresa_id'] ?? auth()->user()?->empresa_id,
        ]);

        // Actualizar existencias
        $existencia->cantidad_disponible = $cantidadPosterior;
        $existencia->costo_promedio = $nuevoCostoPromedio;
        $existencia->costo_acumulado = $kardex->costo_acumulado;
        $existencia->ultima_salida = now();
        $existencia->save();

        return $kardex;
    }

    /**
     * Obtener el saldo de un artículo en un almacén en una fecha específica
     */
    public static function getSaldoFecha($articuloId, $almacenId, $fecha)
    {
        $ultimoMovimiento = self::where('articulo_id', $articuloId)
            ->where('almacen_id', $almacenId)
            ->where('estado', 'confirmado')
            ->where('fecha_movimiento', '<=', $fecha)
            ->orderBy('fecha_movimiento', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $ultimoMovimiento ? $ultimoMovimiento->cantidad_posterior : 0;
    }

    /**
     * Obtener el reporte de kardex completo
     */
    public static function getReporteKardex($articuloId, $almacenId, $fechaInicio, $fechaFin)
    {
        return self::where('articulo_id', $articuloId)
            ->where('almacen_id', $almacenId)
            ->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin])
            ->where('estado', 'confirmado')
            ->orderBy('fecha_movimiento')
            ->orderBy('id')
            ->get();
    }
}