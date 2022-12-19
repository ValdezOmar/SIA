<?php

namespace App\Models\Empleado;

use App\Models\Correspondencia\CorCite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;
    // protected $table="cargos";

    //Relacion uno a muchos con funcionario
    public function empleados(){
        return $this->hasMany(User::class);
    }

    //Relacion uno a uno con unidades
    public function cargo(){
        return $this->hasOne(Cargo::class);
    }

    //Relacion uno a muchos con funcionario
    public function cites(){
        return $this->hasMany(CorCite::class);
    }

}
