<?php

namespace App\Models\Correspondencia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorEstado extends Model
{
    use HasFactory;
    //Relacion uno a muchos con tramite
    public function tramites(){
        return $this->hasMany(CorTramite::class);
    }


}

