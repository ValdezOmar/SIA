<?php

namespace App\Models\Compras;

use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompra extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_ordenes_compra';

    protected $guarded = [];

    protected $casts = [
        'fecha_orden' => 'date',
        'fecha_entrega_estimada' => 'date',
        'fecha_entrega_real' => 'date',
        'fecha_aprobacion' => 'datetime',
        'subtotal' => 'decimal:6',
        'descuento' => 'decimal:6',
        'impuesto' => 'decimal:6',
        'total' => 'decimal:6',
        'tasa_cambio' => 'decimal:6',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $model->codigo = self::generarCodigo();
            }
        });
    }

    // ========== RELACIONES ==========

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function solicitud()
    {
        return $this->belongsTo(SolicitudCompra::class);
    }

    public function cotizacionProveedor()
    {
        return $this->belongsTo(CotizacionProveedor::class);
    }

    public function detalles()
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'orden_id')->orderBy('linea');
    }

    public function recepciones()
    {
        return $this->hasMany(Recepcion::class);
    }

    public function facturas()
    {
        return $this->hasMany(FacturaCompra::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
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

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['borrador', 'enviada', 'confirmada']);
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
            'enviada' => 'Enviada',
            'confirmada' => 'Confirmada',
            'parcial' => 'Parcial',
            'recibida' => 'Recibida',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
            default => $this->estado,
        };
    }

    public function getTotalItemsAttribute()
    {
        return $this->detalles()->sum('cantidad');
    }

    public function getCantidadRecibidaAttribute()
    {
        return $this->detalles()->sum('cantidad_recibida');
    }

    public function getPorcentajeRecibidoAttribute()
    {
        $total = $this->total_items;
        if ($total == 0) return 0;
        return ($this->cantidad_recibida / $total) * 100;
    }

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $gestion = date('y');
        $prefijo = 'OC-' . $gestion;

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

    public function enviar()
    {
        $this->estado = 'enviada';
        $this->save();
        return $this;
    }

    public function confirmar()
    {
        $this->estado = 'confirmada';
        $this->save();
        return $this;
    }

    public function cancelar($motivo = null)
    {
        $this->estado = 'cancelada';
        if ($motivo) {
            $this->observaciones = ($this->observaciones ? $this->observaciones . "\n" : '') . 'Cancelada: ' . $motivo;
        }
        $this->save();
        return $this;
    }

    public function actualizarEstado()
    {
        $total = $this->detalles()->sum('cantidad');
        $recibido = $this->detalles()->sum('cantidad_recibida');

        if ($recibido == 0) {
            $this->estado = 'confirmada';
        } elseif ($recibido < $total) {
            $this->estado = 'parcial';
        } else {
            $this->estado = 'recibida';

            $recepcionesCompletas = $this->recepciones()->where('estado', 'completada')->count();
            if ($recepcionesCompletas > 0 && $this->recepciones()->count() == $recepcionesCompletas) {
                $this->estado = 'completada';
            }
        }

        $this->save();
        return $this;
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

    public function crearRecepcion($data = [])
    {
        $recepcion = Recepcion::create([
            'codigo' => Recepcion::generarCodigo(),
            'orden_compra_id' => $this->id,
            'proveedor_id' => $this->proveedor_id,
            'fecha_recepcion' => $data['fecha_recepcion'] ?? now(),
            'guia_remision' => $data['guia_remision'] ?? null,
            'transportista' => $data['transportista'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'creado_por' => $data['creado_por'] ?? auth()->id(),
            'empresa_id' => $this->empresa_id,
            'estado' => 'pendiente',
        ]);

        // Copiar detalles de la orden
        foreach ($this->detalles as $detalle) {
            if ($detalle->cantidad_pendiente > 0) {
                $recepcion->detalles()->create([
                    'linea' => $detalle->linea,
                    'orden_detalle_id' => $detalle->id,
                    'articulo_id' => $detalle->articulo_id,
                    'codigo_articulo' => $detalle->codigo_articulo,
                    'descripcion_articulo' => $detalle->descripcion_articulo,
                    'unidad_medida' => $detalle->unidad_medida,
                    'cantidad' => $detalle->cantidad_pendiente,
                    'cantidad_aceptada' => 0,
                    'cantidad_rechazada' => 0,
                    'costo_unitario' => $detalle->precio_unitario,
                ]);
            }
        }

        return $recepcion;
    }
}
