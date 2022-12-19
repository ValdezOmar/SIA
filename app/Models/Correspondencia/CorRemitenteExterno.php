<?php

namespace App\Models\Correspondencia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorRemitenteExterno extends Model
{
    use HasFactory;
    //Relacion uno a muchos con hojas de ruta
    public function hojas_rutas()
    {
        return $this->hasMany(CorHojaRuta::class);
    }
}
