<?php

namespace App\Models\RRHH;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Empleado extends Model
{
    use HasFactory;

    protected $fillable = [
        // Información Básica del Empleado
        'nombres',
        'apellidos',
        'ci',
        'fecha_nacimiento',
        'direccion',
        'ubicacion_gps', // Nuevo campo
        'genero',
        'nacionalidad',
        
        // Datos Personales Adicionales
        'estado_civil',
        'cantidad_hijos',
        'telefono_personal',
        'correo_personal',
        'persona_contacto',
        'numero_contacto',
        'nua_cua',
        
        // Datos Laborales
        'activo',
        'foto',
        'fecha_ingreso',
        'fecha_desviculacion',
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
        //'ubicacion_gps' => 'array', // Para almacenar coordenadas como JSON
    ];

    protected $appends = ['foto_url', 'coordenadas'];
    
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
        if ($this->foto && Storage::exists($this->foto)) {
            return Storage::url($this->foto);
        }
        return asset('images/default-avatar.jpg');
    }

    // Accesor para coordenadas
    public function getCoordenadasAttribute()
    {
        if ($this->ubicacion_gps) {
            $coords = json_decode($this->ubicacion_gps, true);
            return [
                'lat' => $coords['lat'] ?? null,
                'lng' => $coords['lng'] ?? null,
                'texto' => $this->ubicacion_gps ? 
                    round($coords['lat'], 6).', '.round($coords['lng'], 6) : null
            ];
        }
        return null;
    }
}