<?php

namespace App\Models\HelpDesk;

use App\Models\RRHH\Empleado;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipo extends Model
{
    use HasFactory;

    protected $table = 'hd_equipos';

   protected $fillable = [
        'codigo',
        'cliente_id',
        'descripcion',
        'marca',
        'modelo',
        'num_serie',
        'observaciones', 
        'tipo_venta',  
        'fecha_entrega', 
        'fecha_instalacion', 
        'fecha_devolucion',  
        'garantia_desde',
        'garantia_hasta',
        'foto_equipo',
        'doc_adjunto',
        'empresa_id',
        'sucursal_id',      
        'tecnico_asignado',
        'tel_soporte',
        'freq_mantenimiento',
        'direccion',
        'ubicacion_gps',
        'activo',
    ];

    protected $casts = [
        'freq_mantenimiento' => 'array',
        'ubicacion_gps' => 'array',
        'activo' => 'boolean',
        'fecha_entrega' => 'date',    
        'fecha_instalacion' => 'date', 
        'fecha_devolucion' => 'date',  
        'garantia_desde' => 'date',
        'garantia_hasta' => 'date',
    ];
    
    protected static function booted()
    {
        static::creating(function ($equipo) {
            // Tomar primeras 3 letras de la marca
            $marca = strtoupper(substr($equipo->marca ?? 'GEN', 0, 3));

            // Tomar primeras 3 letras de la empresa
            $empresa = strtoupper(substr($equipo->empresa?->razon_social ?? 'GEN', 0, 3));

            // Contar equipos existentes con la misma marca y empresa
            $contador = self::where('marca', $equipo->marca)
                ->whereHas('empresa', fn($q) => $q->where('razon_social', $equipo->empresa?->razon_social ?? 'GEN'))
                ->count() + 1;

            // Secuencia con 3 dígitos
            $secuencia = str_pad($contador, 3, '0', STR_PAD_LEFT);

            // Generar código final
            $equipo->codigo = "{$marca}-{$empresa}-{$secuencia}";
        });
    }
    
    // Relaciones con modelos
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(Empleado::class, 'tecnico_asignado');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'equipo_id');
    }
}