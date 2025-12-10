<?php

namespace App\Models\HelpDesk;

use App\Models\RRHH\Empleado;
use App\Models\Sistema\Area;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evento extends Model
{
    use HasFactory;

    protected $table = 'hd_eventos';

    protected $fillable = [
        'hd_ticket_id',
        'remitente_id',
        'encargado_id',
        'destinatario_id',
        'area_origen_id',
        'area_destino_id',
        'estado',
        'fecha_entrada',
        'fecha_recepcion',
        'fecha_salida',
        'observaciones',
        'adjunto_remitente',
        'descripcion',
        'prioridad',
        'adjunto',
    ];
    //CASTEO DE DATOS
    protected $casts = [
        'adjunto_remitente' => 'array',
        'adjunto' => 'array',
        'fecha_entrada' => 'datetime',
        'fecha_recepcion' => 'datetime',
        'fecha_salida' => 'datetime',
    ];

    // Relaciones
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'hd_ticket_id');
    }

    public function remitente()
    {
        return $this->belongsTo(Empleado::class, 'remitente_id');
    }

    public function encargado()
    {
        return $this->belongsTo(Empleado::class, 'encargado_id');
    }

    public function destinatario()
    {
        return $this->belongsTo(Empleado::class, 'destinatario_id');
    }

    public function areaOrigen()
    {
        return $this->belongsTo(Area::class, 'area_origen_id');
    }

    public function areaDestino()
    {
        return $this->belongsTo(Area::class, 'area_destino_id');
    }
}