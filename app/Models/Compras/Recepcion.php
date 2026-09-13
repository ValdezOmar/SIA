<?php

namespace App\Models\Compras;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\MovimientoInventario;
use App\Models\Sistema\Empresa;
use App\Models\User;
use App\Services\Inventario\TrazabilidadInventarioService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Recepcion extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_recepciones';

    protected $guarded = [];

    protected $casts = [
        'fecha_recepcion' => 'date',
        'inventario_procesado_at' => 'datetime',
        'tasa_cambio' => 'decimal:6',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (isset($model->attributes['detalles'])) {
                unset($model->attributes['detalles']);
            }

            if (isset($model->detalles)) {
                unset($model->detalles);
            }
        });

        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $model->codigo = self::generarCodigo();
            }

            if (Auth::check()) {
                $model->creado_por = $model->creado_por ?? Auth::id();
            }
        });

    }

    // ========== RELACIONES ==========

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function detalles()
    {
        return $this->hasMany(RecepcionDetalle::class)->orderBy('linea');
    }

    public function gastosAdicionales()
    {
        return $this->hasMany(GastoAdicionalCompra::class, 'recepcion_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // ========== SCOPES ==========

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    // ========== ACCESORS ==========

    public function getEstadoLabelAttribute()
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'parcial' => 'Parcial',
            'completada' => 'Completada',
            'rechazada' => 'Rechazada',
            default => $this->estado,
        };
    }

    public function getTotalItemsAttribute()
    {
        return $this->detalles()->count();
    }

    public function getTotalAceptadosAttribute()
    {
        return $this->detalles()->where('cantidad_aceptada', '>', 0)->count();
    }

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $gestion = date('y');
        $prefijo = 'REC-'.$gestion;

        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', $prefijo.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->codigo, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo.str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }

    public function procesarEntradaInventario()
    {
        return DB::transaction(function () {
            static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $this->refresh();

            if ($this->inventario_procesado_at) {
                throw new RuntimeException('El inventario de esta recepción ya fue procesado.');
            }

            $almacen = $this->almacen;

            $requiereInventario = $this->detalles()->where('cantidad_aceptada', '>', 0)
                ->whereHas('articulo', fn ($query) => $query->where('inventariable', true))->exists();

            if ($requiereInventario && ! $almacen?->activo) {
                throw new RuntimeException('Seleccione un almacén activo antes de procesar el ingreso.');
            }

            if (! $this->detalles()->where('cantidad_aceptada', '>', 0)->exists()) {
                throw new RuntimeException('Registre al menos una cantidad aceptada antes de procesar el ingreso.');
            }

            $detallesInventariables = $this->detalles
                ->filter(fn (RecepcionDetalle $detalle): bool => (float) $detalle->cantidad_aceptada > 0 && (bool) $detalle->articulo?->inventariable)
                ->values();
            $gastosProrrateados = $this->prorratearGastosCapitalizables($detallesInventariables);

            foreach ($this->detalles as $detalle) {
                if ($detalle->cantidad_aceptada <= 0) {
                    continue;
                }

                $articulo = $detalle->articulo;

                if (! $articulo->inventariable) {
                    if ($ordenDetalle = $detalle->ordenDetalle) {
                        $ordenDetalle->cantidad_recibida += $detalle->cantidad_aceptada;
                        $ordenDetalle->save();
                    }

                    continue;
                }

                $gastoAsignado = (float) ($gastosProrrateados[$detalle->id] ?? 0);
                $costoBase = (float) ($detalle->costo_unitario_base ?: ((float) $detalle->costo_unitario * (float) $this->tasa_cambio));
                $costoUnitarioInventario = round($costoBase + ($gastoAsignado / max((float) $detalle->cantidad_aceptada, 1)), 6);
                $detalle->updateQuietly(['costo_unitario_base' => $costoBase, 'gasto_adicional_base' => $gastoAsignado]);

                // Kardex siempre se valoriza en BOB; el detalle conserva el importe original.
                $kardex = Kardex::registrarEntrada([
                    'articulo_id' => $articulo->id,
                    'almacen_id' => $almacen->id,
                    'tipo_movimiento' => 'compra',
                    'cantidad' => $detalle->cantidad_aceptada,
                    'costo_unitario' => $costoUnitarioInventario,
                    'documento_tipo' => 'recepcion',
                    'documento_id' => $this->id,
                    'documento_codigo' => $this->codigo,
                    'observaciones' => 'Recepción de compra '.$this->codigo,
                    'empresa_id' => $this->empresa_id,
                ]);

                // Actualizar detalle de la orden de compra
                $ordenDetalle = $detalle->ordenDetalle;
                if ($ordenDetalle) {
                    $ordenDetalle->cantidad_recibida += $detalle->cantidad_aceptada;
                    $ordenDetalle->save();
                }

                // Crear movimiento de inventario
                $movimiento = MovimientoInventario::create([
                    'articulo_id' => $articulo->id,
                    'almacen_id' => $almacen->id,
                    'tipo' => 'entrada_compra',
                    'cantidad' => $detalle->cantidad_aceptada,
                    'costo_unitario' => $costoUnitarioInventario,
                    'costo_total' => round($costoUnitarioInventario * (float) $detalle->cantidad_aceptada, 6),
                    'documento_tipo' => 'recepcion',
                    'documento_id' => $this->id,
                    'fecha' => now(),
                    'observacion' => 'Recepción de compra '.$this->codigo,
                    'kardex_id' => $kardex->id,
                    'estado' => 'confirmado',
                ]);

                app(TrazabilidadInventarioService::class)->registrarEntrada($movimiento, $articulo, [
                    'cantidad' => $detalle->cantidad_aceptada,
                    'series' => $detalle->series,
                    'lotes' => $detalle->lotes,
                ]);
            }

            $this->actualizarEstado();
            if ($this->ordenCompra) {
                $this->ordenCompra->actualizarEstado();
            }

            $this->forceFill(['inventario_procesado_at' => now()])->saveQuietly();

            return $this;
        });
    }

    public function actualizarEstado()
    {
        $totalItems = $this->detalles()->count();
        $totalAceptados = $this->detalles()->where('cantidad_aceptada', '>', 0)->count();

        if ($totalAceptados == 0) {
            $this->estado = 'pendiente';
        } elseif ($totalAceptados < $totalItems) {
            $this->estado = 'parcial';
        } else {
            $this->estado = 'completada';
        }

        $this->save();

        return $this;
    }

    public function completar()
    {
        $this->estado = 'completada';
        $this->save();

        return $this;
    }

    public function rechazar($motivo = null)
    {
        $this->estado = 'rechazada';
        if ($motivo) {
            $this->observaciones = ($this->observaciones ? $this->observaciones."\n" : '').'Rechazada: '.$motivo;
        }
        $this->save();

        return $this;
    }
    private function prorratearGastosCapitalizables($detalles): array
    {
        if ($detalles->isEmpty()) return [];
        $asignados = $detalles->mapWithKeys(fn (RecepcionDetalle $detalle) => [$detalle->id => 0.0])->all();
        foreach ($this->gastosAdicionales()->where('capitalizable', true)->get() as $gasto) {
            $porCantidad = $gasto->criterio_prorrateo === 'cantidad';
            $factor = fn (RecepcionDetalle $detalle): float => $porCantidad
                ? (float) $detalle->cantidad_aceptada
                : (float) $detalle->cantidad_aceptada * (float) ($detalle->costo_unitario_base ?: ((float) $detalle->costo_unitario * (float) $this->tasa_cambio));
            $denominador = (float) $detalles->sum($factor);
            if ($denominador <= 0) continue;
            foreach ($detalles as $detalle) $asignados[$detalle->id] += round((float) $gasto->monto_base * ($factor($detalle) / $denominador), 6);
        }
        return $asignados;
    }
}
