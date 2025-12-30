<?php

namespace App\Models\HelpDesk;

use App\Models\RRHH\Empleado;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

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
        static::creating(function (Equipo $equipo) {

            for ($intentos = 0; $intentos < 3; $intentos=+1) {
                try {

                    DB::transaction(function () use ($equipo) {

                        $marca = strtoupper(substr($equipo->marca ?? 'GEN', 0, 3));
                        $empresa = strtoupper(
                            substr($equipo->empresa?->razon_social ?? 'GEN', 0, 3)
                        );

                        $ultimoCodigo = DB::table('hd_equipos')
                            ->where('marca', $equipo->marca)
                            ->where('empresa_id', $equipo->empresa_id)
                            ->where('codigo', 'like', "{$marca}-{$empresa}-%")
                            ->lockForUpdate()
                            ->orderByDesc('id')
                            ->value('codigo');

                        $siguiente = $ultimoCodigo
                            ? ((int) substr($ultimoCodigo, -3)) + 1
                            : 1;

                        $equipo->codigo = sprintf(
                            '%s-%s-%03d',
                            $marca,
                            $empresa,
                            $siguiente
                        );
                    });

                    // Si llega aquí, se generó correctamente
                    return;
                } catch (QueryException $e) {

                    // Duplicate entry (MySQL / MariaDB)
                    if (($e->errorInfo[1] ?? null) !== 1062) {
                        throw $e;
                    }

                    usleep(100000); // 100 ms
                }
            }

            throw new \RuntimeException(
                'No se pudo generar un código único para el equipo después de varios intentos.'
            );
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

    public function sucursalRelacion()
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