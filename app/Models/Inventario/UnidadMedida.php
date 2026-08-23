<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    use Concerns\GeneraCodigoInventario;

    protected $table = 'alm_unidades_medida';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $unidad): void {
            if (empty($unidad->codigo)) {
                $base = strtoupper(trim($unidad->abreviatura ?: $unidad->nombre));
                $unidad->codigo = self::query()->where('codigo', $base)->exists()
                    ? self::codigoCorrelativo($base)
                    : $base;
            }
        });
    }

    public function articulos()
    {
        return $this->hasMany(Articulo::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
