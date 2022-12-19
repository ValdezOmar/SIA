<?php

namespace App\Models\Empleado;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    use HasFactory;
    protected $table="personal";
    protected $fillable = [
        'nombres',
        'apellidos',
        'fecha_nac',
        'CI',
        'direccion',
        'telefono_1',
        'telefono_2',
        'email_personal',
        'descripcion',
        'adjunto',
        'foto'
    ];

    public function getRouteKeyName()
    {
        return "nombres";
    }
    public function empleado(){
        return $this->hasOne(User::class);
    }
}
