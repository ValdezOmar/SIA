<?php

namespace App\Models\HelpDesk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'com_clientes';

    protected $fillable = [
        'razon_social',
        'ci_nit',
        'telefono',
        'correo',
        'tipo_institucion',
        'direccion',
        'ciudad',
        'observaciones',
        'activo',
    ];

    // Relaciones
    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'cliente_id');
    }
}