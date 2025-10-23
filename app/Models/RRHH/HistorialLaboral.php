<?php

namespace App\Models\RRHH;

use App\Models\Sistema\Cargo;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HistorialLaboral extends Model
{
    use HasFactory;

    protected $table = 'rh_historial_laboral';

    protected $fillable = [
        'empleado_id',
        'empresa_id',
        'cargo_id',
        'sucursal_id',
        'fecha_inicio',
        'fecha_fin',
        'fecha_baja',
        'salario',
        'tipo_contrato',
        'seguro_medico',        
        'correo_corporativo',
        'numero_corporativo',
        'observaciones',
        'documento',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_baja' => 'date',
        'salario' => 'float',
        'activo' => 'boolean',
    ];

    //Relaciones
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    //Evento: solo un registro activo por empleado
    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->activo) {
                // Pone todos los demás en falso
                static::where('empleado_id', $model->empleado_id)
                    ->where('id', '!=', $model->id)
                    ->update(['activo' => false]);
            }
        });
    }
}