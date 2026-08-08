<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudCompraDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_solicitudes_detalle';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'cantidad_atendida' => 'decimal:6',
        'precio_estimado' => 'decimal:6',
        'subtotal' => 'decimal:6',
        'linea' => 'integer',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->linea) {
                $ultimo = static::where('solicitud_id', $model->solicitud_id)
                    ->orderBy('linea', 'desc')
                    ->first();
                $model->linea = $ultimo ? $ultimo->linea + 1 : 1;
            }
        });
    }

    // ========== RELACIONES ==========

    public function solicitud()
    {
        return $this->belongsTo(SolicitudCompra::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    // ========== ACCESORS ==========

    public function getSubtotalCalculadoAttribute()
    {
        return $this->cantidad * $this->precio_estimado;
    }

    public function getCantidadPendienteAttribute()
    {
        return $this->cantidad - $this->cantidad_atendida;
    }

    public function getPorcentajeAtendidoAttribute()
    {
        if ($this->cantidad == 0) return 0;
        return ($this->cantidad_atendida / $this->cantidad) * 100;
    }

    public function getEstaAtendidoAttribute()
    {
        return $this->cantidad_atendida >= $this->cantidad;
    }
}