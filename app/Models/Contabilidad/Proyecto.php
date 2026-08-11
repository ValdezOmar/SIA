<?php

namespace App\Models\Contabilidad;

use App\Models\Sistema\Empresa;
use App\Models\User;
use App\Models\Ventas\Cliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proyecto extends Model
{
    use SoftDeletes;

    protected $table = 'con_proyectos';

    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'presupuesto' => 'decimal:6',
        'gastado' => 'decimal:6',
    ];

    // ========== RELACIONES ==========

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function detallesAsiento()
    {
        return $this->hasMany(AsientoDetalle::class);
    }

    public function saldos()
    {
        return $this->hasMany(SaldoCuenta::class);
    }

    // ========== SCOPES ==========

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeByEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    // ========== ACCESORS ==========

    public function getEstadoLabelAttribute()
    {
        return match($this->estado) {
            'planeacion' => 'Planeación',
            'activo' => 'Activo',
            'pausado' => 'Pausado',
            'finalizado' => 'Finalizado',
            'cancelado' => 'Cancelado',
            default => $this->estado,
        };
    }

    public function getSaldoAttribute()
    {
        return $this->presupuesto - $this->gastado;
    }

    public function getPorcentajeGastadoAttribute()
    {
        if ($this->presupuesto == 0) return 0;
        return ($this->gastado / $this->presupuesto) * 100;
    }

    public function getFullNameAttribute()
    {
        return $this->codigo . ' - ' . $this->nombre;
    }
}