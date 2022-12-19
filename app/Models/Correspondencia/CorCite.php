<?php

namespace App\Models\Correspondencia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorCite extends Model
{
    use HasFactory;
    protected $fillable = [
        'cite',
        'asunto',
        'cargo_id',
        'elaborador_id'
    ];
    //Relacion unoo a uno con hoja de ruta
    public function hoja_ruta()
    {
        return $this->hasOne(CorHojaRuta::class);
    }
    //Relacion muchis a uno con empleado
    public function empleado()
    {
        return $this->belongsTo(User::class);
    }
    //Relacion muchos a uno con cargo
    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }
}

