<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    protected $table = 'alm_lotes';

    protected $guarded = [];

    protected $casts = [
        'fecha_fabricacion' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    // ========== RELACIONES ==========

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function stocks()
    {
        return $this->hasMany(LoteStock::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoLote::class);
    }

    // ========== SCOPES ==========

    public function scopeVigente($query)
    {
        return $query->whereNull('fecha_vencimiento')
            ->orWhere('fecha_vencimiento', '>=', now());
    }

    public function scopeVencido($query)
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now());
    }

    public function scopePorVencer($query, $dias = 30)
    {
        $fechaInicio = now();
        $fechaFin = now()->addDays($dias);
        
        return $query->where('fecha_vencimiento', '>=', $fechaInicio)
            ->where('fecha_vencimiento', '<=', $fechaFin);
    }

    // ========== ACCESORS ==========

    public function getDiasRestantesAttribute()
    {
        if (!$this->fecha_vencimiento) {
            return null;
        }
        
        return now()->diffInDays($this->fecha_vencimiento, false);
    }

    public function getEstaVencidoAttribute()
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }
        
        return $this->fecha_vencimiento < now();
    }

    public function getStockTotalAttribute()
    {
        return $this->stocks()->sum('cantidad');
    }
}