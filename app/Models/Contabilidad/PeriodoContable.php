<?php

namespace App\Models\Contabilidad;

use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PeriodoContable extends Model
{
    protected $table = 'con_periodos_contables';

    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_cierre' => 'date',
    ];

    // ========== RELACIONES ==========

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function cerradoPor()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    // ========== SCOPES ==========

    public function scopeAbiertos($query)
    {
        return $query->where('estado', 'abierto');
    }

    public function scopeByEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    // ========== MÉTODOS ==========

    public function cerrar($usuarioId = null)
    {
        $this->estado = 'cerrado';
        $this->fecha_cierre = now();
        $this->cerrado_por = $usuarioId ?? Auth::id();
        $this->save();

        // Generar asiento de cierre
        $this->generarAsientoCierre();

        return $this;
    }

    public function generarAsientoCierre()
    {
        // Implementar lógica de cierre contable
    }

    public function estaAbierto()
    {
        return $this->estado === 'abierto';
    }

    public function getNombreAttribute()
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return ($meses[$this->mes] ?? $this->mes) . ' ' . $this->anio;
    }
}