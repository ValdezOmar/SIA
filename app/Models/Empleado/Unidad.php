<?php

namespace App\Models\Empleado;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    use HasFactory;
    protected $table="unidades";

    //Relacion uno a uno inversa con cargos
    public function cargo(){
        // $profile = Empleado::where('user_id', $this->id)->first();
        return $this->hasOne(Cargo::class);
    }
    //realcion para ver contenido de unidades
    public function empleado(){
        return $this->hasManyThrough(Empleado::class, Cargo::class);
    }
}
