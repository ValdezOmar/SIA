<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionHorarioAsistencia extends Model
{
    use HasFactory;

    protected $table = 'rh_asignaciones_horario_asistencia';

    protected $fillable = [
        'horario_asistencia_id',
        'empleado_id',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    public function horario(): BelongsTo
    {
        return $this->belongsTo(HorarioAsistencia::class, 'horario_asistencia_id');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
