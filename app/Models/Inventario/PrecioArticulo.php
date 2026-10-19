<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class PrecioArticulo extends Model
{
    protected $table = 'alm_precios_articulos';

    protected $guarded = [];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (self $precio): void {
            $precio->registrarCambio('creado', null, $precio->getAttributes()['precio'] ?? null, null, $precio->lista_precio_id);
        });

        static::updated(function (self $precio): void {
            $cambiosAuditables = ['precio', 'lista_precio_id', 'articulo_id'];

            if (! $precio->wasChanged($cambiosAuditables)) {
                return;
            }

            $original = array_merge($precio->getRawOriginal(), $precio->getPrevious());
            $nuevo = $precio->getAttributes();
            $listaAnterior = $original['lista_precio_id'] ?? null;
            $listaNueva = $nuevo['lista_precio_id'] ?? null;
            $articuloId = $nuevo['articulo_id'] ?? $original['articulo_id'] ?? null;

            $precio->registrarCambio(
                'actualizado',
                $original['precio'] ?? null,
                $nuevo['precio'] ?? null,
                $listaAnterior,
                $listaNueva,
                $articuloId,
            );
        });

        static::deleted(function (self $precio): void {
            $attributes = $precio->getAttributes();
            $precio->registrarCambio(
                'eliminado',
                $attributes['precio'] ?? null,
                null,
                $attributes['lista_precio_id'] ?? null,
                null,
                $attributes['articulo_id'] ?? null,
            );
        });
    }

    private function registrarCambio(
        string $accion,
        mixed $precioAnterior,
        mixed $precioNuevo,
        mixed $listaAnteriorId,
        mixed $listaNuevaId,
        mixed $articuloId = null,
    ): void {
        $articuloId ??= $this->articulo_id;
        $usuario = Auth::user();
        $registro = PrecioArticuloHistorial::query()
            ->where('precio_articulo_id', $this->getKey())
            ->where('articulo_id', $articuloId)
            ->where('accion', $accion)
            ->where('lista_precio_anterior_id', $listaAnteriorId)
            ->where('lista_precio_nuevo_id', $listaNuevaId)
            ->where('precio_anterior', $precioAnterior)
            ->where('precio_nuevo', $precioNuevo)
            ->latest('id')
            ->first();

        if (! $registro) {
            return;
        }

        $registro->forceFill([
            'usuario_id' => $usuario?->getAuthIdentifier(),
            'usuario_nombre' => $usuario?->name ?? 'Sistema',
        ])->saveQuietly();
    }

    // Relaciones
    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function listaPrecio(): BelongsTo
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(PrecioArticuloHistorial::class, 'precio_articulo_id');
    }

    // Scopes útiles
    public function scopeByArticulo($query, $articuloId)
    {
        return $query->where('articulo_id', $articuloId);
    }

    public function scopeByLista($query, $listaId)
    {
        return $query->where('lista_precio_id', $listaId);
    }

    public function scopeByMoneda($query, $moneda)
    {
        return $query->whereHas('listaPrecio', function ($q) use ($moneda) {
            $q->where('moneda', $moneda);
        });
    }

    // Accessor para precio formateado
    public function getPrecioFormateadoAttribute(): string
    {
        $moneda = $this->listaPrecio?->moneda ?? 'BOB';
        return number_format($this->precio, 2) . ' ' . $moneda;
    }
}
