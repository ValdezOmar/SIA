<?php

namespace App\Models\Compras;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GastoAdicionalCompra extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_gastos_adicionales';

    protected $guarded = [];

    protected $casts = [
        'monto' => 'decimal:6',
        'tasa_cambio' => 'decimal:6',
        'monto_base' => 'decimal:6',
        'capitalizable' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $gasto): void {
            $gasto->moneda = strtoupper($gasto->moneda ?: 'BOB');
            $gasto->tasa_cambio = $gasto->moneda === 'BOB' ? 1 : max(0.000001, (float) $gasto->tasa_cambio);
            $gasto->monto_base = round((float) $gasto->monto * (float) $gasto->tasa_cambio, 6);
        });
    }

    public function recepcion()
    {
        return $this->belongsTo(Recepcion::class);
    }
}