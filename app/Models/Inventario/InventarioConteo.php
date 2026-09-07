<?php

namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InventarioConteo extends Model
{
    protected $table = 'alm_inventario_conteos';

    protected $guarded = [];

    protected $casts = [
        'stock_sistema' => 'decimal:6', 'stock_reservado' => 'decimal:6',
        'cantidad_contada' => 'decimal:6', 'contado_at' => 'datetime', 'revisado_at' => 'datetime',
    ];

    public function inventarioFisico()
    {
        return $this->belongsTo(InventarioFisico::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function contador()
    {
        return $this->belongsTo(User::class, 'contado_por');
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function getDiferenciaAttribute(): ?float
    {
        return $this->cantidad_contada === null ? null : round((float) $this->cantidad_contada - (float) $this->stock_sistema, 6);
    }
}
