<?php

namespace App\Models\Sistema;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'conf_sucursales';

    protected $fillable = [
        'sociedad_id',
        'nombre',
        'direccion',
        'ciudad',
        'pais',
        'telefono',
        'activo',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'sociedad_id');
    }
}