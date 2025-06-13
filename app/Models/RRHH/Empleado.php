<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Empleado extends Model
{
    use HasFactory;
    protected $table = 'rh_empleados';

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

        // Datos Laborales
        'activo',
        'foto',
        'fecha_ingreso',
        'fecha_desvinculacion',
        'estado_contrato',
        'afp',
        'caja_salud',
        'correo_corporativo',
        'numero_corporativo',
        'cargo',
        'sucursal',
        'empresa',
        'salario', // Nuevo campo
    ];

    protected $casts = [
        'salario' => 'float',
        'ubicacion_gps' => 'array', // Para almacenar coordenadas como JSON
    ];

    //protected $appends = ['foto_url', 'coordenadas'];

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'user_id', 'ci');
    }

    // Accesor para nombre completo
    public function getFullNameAttribute()
    {
        return $this->nombres . ' ' . $this->apellidos;
    }

    // Foto de perfil por defecto
    public function getFotoUrlAttribute()
    {
        if (!$this->foto) {
            return asset('images/default-avatar.jpg');
        }

        // Si ya es una URL completa
        if (filter_var($this->foto, FILTER_VALIDATE_URL)) { 
            return $this->foto;
        }

        // Verificar si la foto existe en storage
        $path = 'empleados/' . basename($this->foto);

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
            'texto' => "Lat: {$this->ubicacion_gps['lat']}, Lng: {$this->ubicacion_gps['lng']}"
        ] : null;
    }
    // Verificacion de email de empleado
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'correo_corporativo', 'email');
    }
    //Modelo de comprovaciond de foto
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (is_array($model->foto)) {
                $model->foto = null;
            }
        });
    }
}