<?php

namespace App\Models\Correspondencia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorTramite extends Model
{
    use HasFactory;
    protected $fillable = [
        'remitente',
        'destinatario',
        'proveido',
        'fecha_recepcion',
        'fecha_derivacion',
        'fecha_rechazo',
        'fecha_anulado',
        'fecha_archivo',
        'estado'
    ];

    //Relacion uno a muchos con empleado
    public function empleados(){
        return $this->belongsTo(User::class);
    }

    //Relacion uno a muchos con tramite
    public function tramites(){
        return $this->belongsTo(CorEstado::class);
    }

    //Realicion muchos a muchos con hojas de ruta
    public function hojaRutas()
    {
        return $this->belongsToMany(CorHojaRuta::class);
    }






}
