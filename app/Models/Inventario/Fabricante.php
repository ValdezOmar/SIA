<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;

class Fabricante extends Model
{
    use Concerns\GeneraCodigoInventario;

    protected $table = 'alm_fabricantes';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $fabricante): void {
            $fabricante->empresa_id ??= auth()->user()?->empresa_id;
            if (empty($fabricante->codigo)) {
                $fabricante->codigo = self::codigoDosIniciales($fabricante->nombre);
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
