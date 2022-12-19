<?php

namespace App\Models\Correspondencia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorHojaRuta extends Model
{
    use HasFactory;
    protected $table = 'cor_hoja_rutas';
    protected $fillable = [
        'hoja_ruta',
        'fecha_ingreso',
        'cite_externo',
        'hr_externo',
        'cite_interno_id',
        'tipo_proceso_id',
        'remitente_interno_id',
        'remitente_externo_id',
        'asunto'

    ];

    //relacion muchos a uno con empleados
    public function empleado(){
        return $this->belongsTo(User::class);
    }

    //relacion uno a uno con cite
    public function cite(){
        return $this->belongsTo(CorCite::class);
    }

     //relacion muchos a uno con hojas de ruta
     public function remitente_externo(){
        return $this->belongsTo(CorRemitenteExterno::class);
    }

     //relacion muchos a uno con tipo proceso
     public function tipo_proceso(){
        return $this->belongsTo(CorTipoProceso::class);
    }

     //Realicion muchos a muchos con tramites
     public function tramites()
     {
         return $this->belongsToMany(CorTramite::class);
     }
}
