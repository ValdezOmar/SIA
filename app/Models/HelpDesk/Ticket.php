<?php

namespace App\Models\HelpDesk;

use App\Models\RRHH\Empleado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'hd_tickets';

    protected $fillable = [
        'codigo',
        'equipo_id',
        'tipo',
        'prioridad',
        'estado',
        'cli_solicitante',
        'cli_telefono',
        'diagnostico',
        'fecha_solicitada',
        'fecha_programada',
        'adjunto',
        'destinatario_id'
    ];

    protected $casts = [
        'adjunto' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->codigo)) {
                $ticket->codigo = self::generarCodigo();
            }
            // guarda automáticamente el usuario logueado
            $ticket->empleado_creacion = Auth::user()->name ?? 'Sistema';
        });
    }

    //Valores por defecto para nuevos registros     
    protected $attributes = [
        'estado' => 'abierto',
    ];

    //Se establece el nombre de cliente en minusculas y la primera en mayuscula
    public function setCliSolicitanteAttribute($value)
    {
        $this->attributes['cli_solicitante'] = ucwords(strtolower($value));
    }

    //Genera el codigo del ticket automaticamente
    public static function generarCodigo()
    {
        $gestion = date('y');

        $ultimoTicket = self::where('codigo', 'like', $gestion . '%')
            ->orderBy('codigo', 'desc')
            ->first();

        if ($ultimoTicket) {
            $ultimaSerie = intval(substr($ultimoTicket->codigo, 2));
            $nuevaSerie = $ultimaSerie + 1;
        } else {
            $nuevaSerie = 1;
        }

        return $gestion . str_pad($nuevaSerie, 4, '0', STR_PAD_LEFT);
    }
    
    //Linea de eventos para el historial
    public function eventosOrdenados()
    {
        return $this->hasMany(Evento::class, 'hd_ticket_id')
            ->with(['remitente', 'destinatario', 'encargado']) // ¡IMPORTANTE: cargar relaciones!
            ->orderBy('created_at', 'desc');
    }

    public function getGestionAttribute()
    {
        return substr($this->codigo, 0, 2);
    }

    public function getSerieAttribute()
    {
        return intval(substr($this->codigo, 2));
    }

    //Relaciones con modelos
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function eventos()
    {
        return $this->hasMany(Evento::class, 'hd_ticket_id');
    }

    public function destinatario()
    {
        return $this->belongsTo(Empleado::class, 'destinatario_id');
    }
}