<?php

namespace App\Models\Ventas;

use App\Models\Inventario\Articulo;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Cotizacion extends Model
{
    use SoftDeletes;

    protected $table = 'ven_cotizaciones';

    protected $guarded = [];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_validez' => 'date',
        'fecha_entrega_estimada' => 'date',
        'tasa_cambio' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
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

        // ✅ USAR SAVED en lugar de CREATING/UPDATING para calcular totales
        static::saved(function ($model) {
            // Calcular totales desde los detalles
            $model->calcularTotalesDesdeDetalles();
        });

        // ✅ También calcular al crear detalles
        static::created(function ($model) {
            // Ya se ejecutó el saved
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

        // Recargar detalles frescos desde la BD
        $this->load('detalles');

        foreach ($this->detalles as $detalle) {
            $subtotal += floatval($detalle->subtotal ?? 0);
            $descuento += floatval($detalle->descuento ?? 0);
            $impuesto += floatval($detalle->impuesto ?? 0);
            $total += floatval($detalle->total ?? 0);
        }

        $this->subtotal = $subtotal;
        $this->descuento = $descuento;
        $this->impuesto = $impuesto;
        $this->total = $total;

        // Guardar los totales en la base de datos (sin ejecutar eventos)
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

    public function detalles()
    {
        return $this->hasMany(CotizacionDetalle::class)->orderBy('linea');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function pedido()
    {
        return $this->hasOne(Pedido::class, 'cotizacion_id');
    }

    // ========== SCOPES ==========

    public function scopeByEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeVigentes($query)
    {
        return $query->whereIn('estado', ['enviada', 'aprobada'])
            ->where('fecha_validez', '>=', now()->toDateString());
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['borrador', 'enviada']);
    }

    // ========== ACCESORS ==========

    public function getTotalItemsAttribute()
    {
        return $this->detalles()->sum('cantidad');
    }

    public function getEstaVigenteAttribute()
    {
        return $this->estado === 'aprobada' &&
            $this->fecha_validez >= now()->toDateString();
    }

    public function getEstadoLabelAttribute()
    {
        return match ($this->estado) {
            'borrador' => 'Borrador',
            'enviada' => 'Enviada',
            'aprobada' => 'Aprobada',
            'rechazada' => 'Rechazada',
            'convertida' => 'Convertida',
            'expirada' => 'Expirada',
            default => $this->estado,
        };
    }

    public function getEstadoColorAttribute()
    {
        return match ($this->estado) {
            'borrador' => 'gray',
            'enviada' => 'info',
            'aprobada' => 'success',
            'rechazada' => 'danger',
            'convertida' => 'primary',
            'expirada' => 'warning',
            default => 'gray',
        };
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
            'total' => $subtotal - $descuento + $impuesto,
        ]);
    }

    public function convertirPedido($data = [])
    {
        $pedido = Pedido::create([
            'codigo' => Pedido::generarCodigo(),
            'cotizacion_id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'sucursal_id' => $this->sucursal_id,
            'fecha_pedido' => now(),
            'fecha_entrega_estimada' => $this->fecha_entrega_estimada,
            'condicion_pago' => $this->condicion_pago,
            'moneda' => $this->moneda,
            'tasa_cambio' => $this->tasa_cambio,
            'observaciones' => $data['observaciones'] ?? $this->observaciones,
            'vendedor_id' => $this->vendedor_id,
            'empresa_id' => $this->empresa_id,
        ]);

        foreach ($this->detalles as $detalle) {
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

        $this->update(['estado' => 'convertida']);

        return $pedido;
    }

    // Generar código único para la cotización
    public static function generarCodigo()
    {
        $gestion = date('y'); // 26 para 2026
        $prefijo = 'COT-' . $gestion;

        // Buscar el último código con el prefijo de la gestión actual
        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            // Extraer el correlativo (últimos 4 dígitos)
            // Ejemplo: COT-260005 -> extraer '0005'
            $correlativo = intval(substr($ultimo->codigo, -4)) + 1;
        } else {
            // Si no hay códigos para esta gestión, empezar desde 1
            $correlativo = 1;
        }

        // Formatear el correlativo con 4 dígitos
        return $prefijo . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }
}
