<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use App\Models\Sistema\Area;  // Cambiar a Area
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudCompra extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_solicitudes_compra';

    protected $guarded = [];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_requerida' => 'date',
        'fecha_aprobacion' => 'datetime',
        'subtotal' => 'decimal:6',
        'impuesto' => 'decimal:6',
        'total' => 'decimal:6',
    ];

    // ========== RELACIONES ==========

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function detalles()
    {
         return $this->hasMany(SolicitudCompraDetalle::class, 'solicitud_id')->orderBy('linea');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cotizaciones()
    {
        return $this->hasMany(CotizacionProveedor::class);
    }

    public function ordenCompra()
    {
        return $this->hasOne(OrdenCompra::class);
    }

    // ========== SCOPES ==========

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['pendiente', 'en_cotizacion']);
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', 'aprobada');
    }

    // ========== ACCESORS ==========

    public function getEstadoLabelAttribute()
    {
        return match ($this->estado) {
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente',
            'aprobada' => 'Aprobada',
            'rechazada' => 'Rechazada',
            'en_cotizacion' => 'En Cotización',
            'convertida' => 'Convertida',
            default => $this->estado,
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

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', 'SOL-%')
            ->orderBy('id', 'desc')
            ->first();

        $numero = $ultimo ? intval(substr($ultimo->codigo, -6)) + 1 : 1;
        return 'SOL-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    public function aprobar($usuarioId = null)
    {
        $this->estado = 'aprobada';
        $this->aprobado_por = $usuarioId ?? auth()->id();
        $this->fecha_aprobacion = now();
        $this->save();

        return $this;
    }

    public function rechazar($motivo = null)
    {
        $this->estado = 'rechazada';
        $this->observaciones = ($this->observaciones ? $this->observaciones . "\n" : '') . 'Rechazada: ' . $motivo;
        $this->save();

        return $this;
    }

    public function crearOrdenCompra()
    {
        $orden = OrdenCompra::create([
            'codigo' => OrdenCompra::generarCodigo(),
            'proveedor_id' => $this->proveedor_seleccionado ?? null,
            'solicitud_id' => $this->id,
            'fecha_orden' => now(),
            'fecha_entrega_estimada' => now()->addDays(15),
            'moneda' => 'BOB',
            'estado' => 'borrador',
            'empresa_id' => $this->empresa_id,
        ]);

        foreach ($this->detalles as $detalle) {
            $orden->detalles()->create([
                'linea' => $detalle->linea,
                'articulo_id' => $detalle->articulo_id,
                'codigo_articulo' => $detalle->codigo_articulo,
                'descripcion_articulo' => $detalle->descripcion_articulo,
                'unidad_medida' => $detalle->unidad_medida,
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_estimado ?? 0,
                'subtotal' => $detalle->subtotal ?? 0,
                'observaciones' => $detalle->observaciones,
            ]);
        }

        $this->estado = 'convertida';
        $this->save();

        return $orden;
    }
}
