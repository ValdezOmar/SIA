<?php

namespace App\Models\Inventario;

use LogicException;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InventarioEvento extends Model
{
    protected $table = 'alm_inventario_eventos';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['antes' => 'array', 'despues' => 'array', 'created_at' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function conteo()
    {
        return $this->belongsTo(InventarioConteo::class);
    }

    public function inventarioFisico()
    {
        return $this->belongsTo(InventarioFisico::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('La bitácora de inventario no se puede modificar.'));
        static::deleting(fn () => throw new LogicException('La bitácora de inventario no se puede eliminar.'));
    }
}
