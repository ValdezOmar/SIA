<?php

namespace App\Models\RRHH;

use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'rh_asistencias';
    protected $fillable = ['id_equipo', 'user_id', 'fecha', 'hora', 'registro_remoto', 'localizacion', 'justificacion', 'visible'];

    //Array de funciones para la validacion de campos en el form de GPS
    public static function rules(): array
    {
        return [
            'user_id' => 'required|string|max:20',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i:s',
            'registro_remoto' => 'required|boolean',
            'localizacion' => 'required|string|min:5',
            'justificacion' => 'required_if:registro_remoto,true|string|max:500|nullable',
        ];
    }

    //Relacion con Empleado
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'user_id', 'ci');
    }

    //Buscando asistencias usando el CI
    public static function obtenerAsistencias(string $ci, string $fecha)
    {
        Log::debug('Llamando a obtenerAsistencias', [
            'ci' => $ci,
            'fecha' => $fecha
        ]);

        $result = self::where('user_id', $ci)
            ->whereDate('fecha', $fecha)
            ->orderBy('hora')
            ->get();

        Log::debug('Resultado de obtenerAsistencias', [
            'total' => $result->count(),
            'horas' => $result->pluck('hora')->toArray()
        ]);

        return $result;
    }

    
}