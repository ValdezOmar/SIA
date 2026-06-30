<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioArticulo extends Model
{
    protected $table = 'alm_precios_articulos';

    protected $guarded = [];

    protected $casts = [
        'precio' => 'decimal:6',
    ];

    // Relaciones
    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function listaPrecio(): BelongsTo
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    // Scopes útiles
    public function scopeByArticulo($query, $articuloId)
    {
        return $query->where('articulo_id', $articuloId);
    }

    public function scopeByLista($query, $listaId)
    {
        return $query->where('lista_precio_id', $listaId);
    }

    public function scopeByMoneda($query, $moneda)
    {
        return $query->whereHas('listaPrecio', function ($q) use ($moneda) {
            $q->where('moneda', $moneda);
        });
    }

    // Accessor para precio formateado
    public function getPrecioFormateadoAttribute(): string
    {
        $moneda = $this->listaPrecio?->moneda ?? 'BOB';
        return number_format($this->precio, 2) . ' ' . $moneda;
    }
}