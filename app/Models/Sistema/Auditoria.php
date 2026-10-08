<?php

namespace App\Models\Sistema;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'sis_auditorias';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['registrado_at' => 'datetime', 'antes' => 'array', 'despues' => 'array', 'contexto' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(fn () => throw new \LogicException('La auditoría es de solo lectura.'));
        static::deleting(fn () => throw new \LogicException('La auditoría es de solo lectura.'));
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function scopeVisiblePara(Builder $query, User $usuario): Builder
    {
        if ($usuario->hasRole('super_admin') || $usuario->can('ver_todas_auditorias')) {
            return $query;
        }

        if (! $usuario->empresa_id) {
            return $query->where('usuario_id', $usuario->id);
        }

        return $query->where('empresa_id', $usuario->empresa_id)
            ->when($usuario->sucursal_id, fn (Builder $q, $id) => $q->where('sucursal_id', $id));
    }
}
