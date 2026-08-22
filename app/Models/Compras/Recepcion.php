<?php

namespace App\Models\Compras;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\MovimientoInventario;
use App\Services\Inventario\TrazabilidadInventarioService;
use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Recepcion extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_recepciones';

    protected $guarded = [];

    protected $casts = [
        'fecha_recepcion' => 'date',
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

    public function detalles()
    {
        return $this->hasMany(RecepcionDetalle::class)->orderBy('linea');
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
        return match($this->estado) {
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
        $prefijo = 'REC-' . $gestion;

        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->codigo, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }

    public function procesarEntradaInventario()
    {
        $almacen = Almacen::where('activo', true)->first();

        if (!$almacen) {
            return;
        }

        foreach ($this->detalles as $detalle) {
            if ($detalle->cantidad_aceptada <= 0) {
                continue;
            }

            $articulo = $detalle->articulo;

            // Registrar entrada en kardex
            $kardex = Kardex::registrarEntrada([
                'articulo_id' => $articulo->id,
                'almacen_id' => $almacen->id,
                'tipo_movimiento' => 'compra',
                'cantidad' => $detalle->cantidad_aceptada,
                'costo_unitario' => $detalle->costo_unitario,
                'documento_tipo' => 'recepcion',
                'documento_id' => $this->id,
                'documento_codigo' => $this->codigo,
                'observaciones' => 'Recepción de compra ' . $this->codigo,
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
                'costo_unitario' => $detalle->costo_unitario,
                'costo_total' => $detalle->costo_total,
                'documento_tipo' => 'recepcion',
                'documento_id' => $this->id,
                'fecha' => now(),
                'observacion' => 'Recepción de compra ' . $this->codigo,
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
            $this->observaciones = ($this->observaciones ? $this->observaciones . "\n" : '') . 'Rechazada: ' . $motivo;
        }
        $this->save();
        return $this;
    }
}