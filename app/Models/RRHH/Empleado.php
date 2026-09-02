<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Empleado extends Model
{
    use HasFactory;

    protected $table = 'rh_empleados';

    /**
     * Mantiene disponible una fotografía válida en todos los recursos. Cuando
     * no existe una foto cargada se utiliza el avatar predeterminado del sistema.
     */
    protected $appends = ['foto_url'];

    protected $fillable = [
        // Información Básica del Empleado
        'nombres',
        'foto',
        'apellidos',
        'ci',
        'fecha_nacimiento',
        'direccion',
        'ubicacion_gps',
        'genero',
        'nacionalidad',

        // Datos Personales Adicionales
        'estado_civil',
        'cantidad_hijos',
        'telefono_personal',
        'correo_personal',
        'persona_contacto',
        'numero_contacto',
        'persona_parentesco',
        'nua_cua',

        // Estado y archivos propios del empleado
        'activo',
        'afp',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'ubicacion_gps' => 'array',
        'cantidad_hijos' => 'integer',
        'activo' => 'boolean',
    ];

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'user_id', 'ci');
    }

    public function asignacionesHorarioAsistencia()
    {
        return $this->hasMany(AsignacionHorarioAsistencia::class, 'empleado_id');
    }

    // Accesor para nombre completo
    public function getFullNameAttribute()
    {
        return $this->nombres.' '.$this->apellidos;
    }

    // Foto de perfil por defecto
    public function getFotoUrlAttribute()
    {
        if (! $this->foto) {
            return asset('images/default-avatar.jpg');
        }

        // Si ya es una URL completa
        if (filter_var($this->foto, FILTER_VALIDATE_URL)) {
            return $this->foto;
        }

        if ($this->foto_storage_path && $this->exists) {
            return url("/media/empleados/{$this->getKey()}/foto?v=".($this->updated_at?->timestamp ?? 0));
        }

        return asset('images/default-avatar.jpg');
    }

    public function getFotoStoragePathAttribute(): ?string
    {
        if (! is_string($this->foto) || blank($this->foto) || filter_var($this->foto, FILTER_VALIDATE_URL)) {
            return null;
        }

        $path = str_starts_with($this->foto, 'empleados/')
            ? $this->foto
            : 'empleados/'.basename($this->foto);

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    // Accesor para coordenadas formateadas
    public function getCoordenadasAttribute()
    {
        $ubicacion = $this->ubicacion_gps;

        if (! is_array($ubicacion) || ! isset($ubicacion['lat'], $ubicacion['lng'])) {
            return null;
        }

        return [
            'lat' => $ubicacion['lat'],
            'lng' => $ubicacion['lng'],
            'texto' => "Lat: {$ubicacion['lat']}, Lng: {$ubicacion['lng']}",
        ];
    }

    // Modelo de comprovaciond de foto
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (is_array($model->foto)) {
                $model->foto = null;
            }
        });
    }

    public function historialLaboral()
    {
        return $this->hasMany(HistorialLaboral::class, 'empleado_id');
    }

    // Historial de personal
    public function historialActivo()
    {
        return $this->hasOne(HistorialLaboral::class, 'empleado_id')
            ->where('activo', true);
    }
}
