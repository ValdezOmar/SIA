<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CotizacionProveedor extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_cotizaciones_proveedor';

    protected $guarded = [];

    protected $casts = [
        'fecha_cotizacion' => 'date',
        'fecha_validez' => 'date',
        'subtotal' => 'decimal:6',
        'impuesto' => 'decimal:6',
        'descuento' => 'decimal:6',
        'total' => 'decimal:6',
        'tasa_cambio' => 'decimal:6',
    ];

    // ========== RELACIONES ==========

    public function solicitud()
    {
        return $this->belongsTo(SolicitudCompra::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles()
    {
        return $this->hasMany(CotizacionProveedorDetalle::class)->orderBy('linea');
    }

    public function ordenCompra()
    {
        return $this->hasOne(OrdenCompra::class);
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

    public function scopeAceptadas($query)
    {
        return $query->where('estado', 'aceptada');
    }

    public function scopePorProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    // ========== ACCESORS ==========

    public function getEstadoLabelAttribute()
    {
        return match($this->estado) {
            'recibida' => 'Recibida',
            'evaluada' => 'Evaluada',
            'aceptada' => 'Aceptada',
            'rechazada' => 'Rechazada',
            default => $this->estado,
        };
    }

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $gestion = date('y');
        $prefijo = 'COT-P-' . $gestion;

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

    public function aceptar()
    {
        $this->estado = 'aceptada';
        $this->save();
        return $this;
    }

    public function rechazar($motivo = null)
    {
        $this->estado = 'rechazada';
        if ($motivo) {
            $this->observaciones = ($this->observaciones ? $this->observaciones . "\n" : '') . 'Rechazada: ' . $motivo;
        }
        $this->save();
        return $this;
    }
}