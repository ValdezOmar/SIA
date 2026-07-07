<?php

namespace App\Models\Ventas;

use App\Models\Inventario\Articulo;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use SoftDeletes;

    protected $table = 'ven_pedidos';

    protected $guarded = [];

    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_entrega_estimada' => 'date',
        'fecha_entrega_real' => 'date',
        'tasa_cambio' => 'decimal:6',
        'subtotal' => 'decimal:6',
        'descuento' => 'decimal:6',
        'impuesto' => 'decimal:6',
        'total' => 'decimal:6',
        'costo_envio' => 'decimal:6',
    ];

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

    public static function generarCodigo()
    {
        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', 'PED-%')
            ->orderBy('id', 'desc')
            ->first();

        $numero = $ultimo ? intval(substr($ultimo->codigo, -6)) + 1 : 1;
        return 'PED-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}