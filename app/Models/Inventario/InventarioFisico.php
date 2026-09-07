<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventarioFisico extends Model
{
    protected $table = 'alm_inventarios_fisicos';

    protected $guarded = [];

    protected $casts = ['fecha_programada' => 'date', 'iniciado_at' => 'datetime', 'cerrado_at' => 'datetime'];

    public const ESTADOS = [
        'programado' => 'Programado', 'en_conteo' => 'En conteo',
        'en_revision' => 'En revisión', 'cerrado' => 'Cerrado', 'cancelado' => 'Cancelado',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function conteos()
    {
        return $this->hasMany(InventarioConteo::class);
    }

    public function eventos()
    {
        return $this->hasMany(InventarioEvento::class);
    }

    public function scopeDelUsuario(Builder $query, User $user): Builder
    {
        return $query->when($user->empresa_id, fn ($q, $id) => $q->where('empresa_id', $id))
            ->when($user->sucursal_id, fn ($q, $id) => $q->where('sucursal_id', $id));
    }

    public function scopeConProgreso(Builder $query): Builder
    {
        return $query->withCount([
            'conteos',
            'conteos as contados_count' => fn ($q) => $q->whereNotNull('cantidad_contada'),
            'conteos as diferencias_count' => fn ($q) => $q->whereNotNull('cantidad_contada')->whereColumn('cantidad_contada', '!=', 'stock_sistema'),
            'conteos as revisados_count' => fn ($q) => $q->whereNotNull('revisado_at'),
        ]);
    }

    public function getProgresoAttribute(): float
    {
        return $this->conteos_count > 0 ? round(100 * $this->contados_count / $this->conteos_count, 1) : 0;
    }
}
