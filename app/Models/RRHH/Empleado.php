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

        // Verificar si la foto existe en storage
        $path = str_starts_with($this->foto, 'empleados/')
            ? $this->foto
            : 'empleados/'.basename($this->foto);

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset('images/default-avatar.jpg');
    }

    // Accesor para coordenadas formateadas
    public function getCoordenadasAttribute()
    {
        return $this->ubicacion_gps ? [
            'lat' => $this->ubicacion_gps['lat'],
            'lng' => $this->ubicacion_gps['lng'],
            'texto' => "Lat: {$this->ubicacion_gps['lat']}, Lng: {$this->ubicacion_gps['lng']}",
        ] : null;
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
