<?php

namespace App\Models\Correspondencia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorTipoProceso extends Model
{
    use HasFactory;
    // Relacion uno a muchos con hoja de ruta
    public function hoja_ruta()
    {
        return $this->hasMany(CorHojaRuta::class);
    }
}
