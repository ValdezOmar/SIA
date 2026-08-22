<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HorarioAsistencia extends Model
{
    use HasFactory;

    protected $table = 'rh_horarios_asistencia';

    protected $fillable = [
        'nombre',
        'codigo',
        'dias_laborales',
        'hora_entrada',
        'tolerancia_minutos',
        'hora_omision',
        'hora_inicio_almuerzo',
        'hora_fin_almuerzo',
        'hora_salida',
        'requiere_marcacion_almuerzo',
        'activo',
        'predeterminado',
        'observaciones',
    ];

    protected $casts = [
        'dias_laborales' => 'array',
        'tolerancia_minutos' => 'integer',
        'requiere_marcacion_almuerzo' => 'boolean',
        'activo' => 'boolean',
        'predeterminado' => 'boolean',
    ];

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionHorarioAsistencia::class, 'horario_asistencia_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $horario): void {
            if ($horario->predeterminado) {
                $otrosHorarios = static::query();

                if ($horario->exists) {
                    $otrosHorarios->whereKeyNot($horario->getKey());
                }

                $otrosHorarios->update(['predeterminado' => false]);
            }
        });
    }
}
