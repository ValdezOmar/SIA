<?php

namespace App\Models\Ventas;

use App\Models\Inventario\Articulo;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Pedido extends Model
{
    use SoftDeletes;

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
            }

            // Generar código si no tiene
            if (empty($model->codigo)) {
                $model->codigo = self::generarCodigo();
            }
        });

        // ✅ USAR SAVED para calcular totales
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
        return match($this->estado) {
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
        return match($this->estado) {
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
        return match($this->prioridad) {
            'baja' => 'Baja',
            'normal' => 'Normal',
            'alta' => 'Alta',
            'urgente' => 'Urgente',
            default => $this->prioridad,
        };
    }

    public function getPrioridadColorAttribute()
    {
        return match($this->prioridad) {
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
        $prefijo = 'PED-' . $gestion;

        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->codigo, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }
}