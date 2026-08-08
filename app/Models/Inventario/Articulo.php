<?php

namespace App\Models\Inventario;

use App\Models\Compras\ArticuloProveedor;
use App\Models\Sistema\Empresa;
use App\Models\Ventas\CotizacionDetalle;
use App\Models\Ventas\FacturaDetalle;
use App\Models\Ventas\PedidoDetalle;
use Illuminate\Database\Eloquent\Model;

class Articulo extends Model
{
    protected $table = 'alm_articulos';

    protected $fillable = [
        'codigo',
        'codigo_alterno',
        'nombre_comercial',
        'descripcion',
        'caracteristicas',
        'fabricante_id',
        'grupo_articulo_id',
        'unidad_medida_id',
        'inventariable',
        'comprable',
        'vendible',
        'maneja_lotes',
        'maneja_series',
        'requiere_serie_en_salida',
        'metodo_costo',
        'comision',
        'foto_catalogo',
        'documentacion_tecnica',
        'activo',
        'empresa_id'
    ];

    protected $attributes = [
        'comision' => 0,
        'inventariable' => true,
        'comprable' => true,
        'vendible' => true,
        'activo' => true,
        'maneja_lotes' => false,
        'maneja_series' => false,
        'requiere_serie_en_salida' => false,
        'metodo_costo' => 'promedio'
    ];

    protected $casts = [
        'inventariable' => 'boolean',
        'comprable' => 'boolean',
        'vendible' => 'boolean',
        'maneja_lotes' => 'boolean',
        'maneja_series' => 'boolean',
        'requiere_serie_en_salida' => 'boolean',
        'activo' => 'boolean',
        'comision' => 'decimal:2',
        'documentacion_tecnica' => 'array',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($articulo) {
            if (empty($articulo->codigo)) {
                $articulo->codigo = self::generarCodigo($articulo);
            }
        });
    }

    // ========== GENERAR CÓDIGO ==========

    public static function generarCodigo($articulo): string
    {
        $codigoGrupo = 'XX';
        if ($articulo->grupo_articulo_id) {
            $grupo = GrupoArticulo::find($articulo->grupo_articulo_id);
            if ($grupo && $grupo->codigo) {
                $codigoGrupo = strtoupper(substr($grupo->codigo, 0, 2));
            } else {
                $codigoGrupo = strtoupper(substr($grupo->nombre ?? 'XX', 0, 2));
            }
        }

        $codigoFabricante = 'XX';
        if ($articulo->fabricante_id) {
            $fabricante = Fabricante::find($articulo->fabricante_id);
            if ($fabricante && $fabricante->codigo) {
                $codigoFabricante = strtoupper(substr($fabricante->codigo, 0, 2));
            } else {
                $codigoFabricante = strtoupper(substr($fabricante->nombre ?? 'XX', 0, 2));
            }
        }

        $prefijo = $codigoGrupo . '-' . $codigoFabricante;

        $ultimo = self::where('codigo', 'LIKE', $prefijo . '-%')
            ->orderBy('codigo', 'desc')
            ->first();

        if ($ultimo) {
            $partes = explode('-', $ultimo->codigo);
            $numero = intval(end($partes));
            $correlativo = str_pad($numero + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $correlativo = '001';
        }

        return $prefijo . '-' . $correlativo;
    }

    public static function previsualizarCodigo($grupoId, $fabricanteId): string
    {
        $codigoGrupo = 'XX';
        if ($grupoId) {
            $grupo = GrupoArticulo::find($grupoId);
            if ($grupo && $grupo->codigo) {
                $codigoGrupo = strtoupper(substr($grupo->codigo, 0, 2));
            }
        }

        $codigoFabricante = 'XX';
        if ($fabricanteId) {
            $fabricante = Fabricante::find($fabricanteId);
            if ($fabricante && $fabricante->codigo) {
                $codigoFabricante = strtoupper(substr($fabricante->codigo, 0, 2));
            }
        }

        $prefijo = $codigoGrupo . '-' . $codigoFabricante;

        $ultimo = self::where('codigo', 'LIKE', $prefijo . '-%')
            ->orderBy('codigo', 'desc')
            ->first();

        if ($ultimo) {
            $partes = explode('-', $ultimo->codigo);
            $numero = intval(end($partes));
            $correlativo = str_pad($numero + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $correlativo = '001';
        }

        return $prefijo . '-' . $correlativo;
    }

    // ========== STOCK ==========

    public function getStockTotalAttribute()
    {
        return $this->existencias()->sum('cantidad_disponible');
    }

    public function getStockPorAlmacenAttribute()
    {
        return $this->existencias()
            ->with('almacen')
            ->get()
            ->mapWithKeys(function ($existencia) {
                return [$existencia->almacen->nombre => $existencia->cantidad_disponible];
            })
            ->toArray();
    }

    // ========== MUTATORS ==========

    public function setManejaLotesAttribute($value)
    {
        $this->attributes['maneja_lotes'] = $value;
        if ($value) {
            $this->attributes['maneja_series'] = false;
            $this->attributes['requiere_serie_en_salida'] = false;
        }
    }

    public function setManejaSeriesAttribute($value)
    {
        $this->attributes['maneja_series'] = $value;
        if ($value) {
            $this->attributes['maneja_lotes'] = false;
        }
    }

    public function setDocumentacionTecnicaAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['documentacion_tecnica'] = json_encode($value);
        } else {
            $this->attributes['documentacion_tecnica'] = $value;
        }
    }

    public function getDocumentacionTecnicaAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        return json_decode($value, true) ?? [];
    }

    // ========== RELACIONES ==========

    // ✅ Relación con kardex (historial de movimientos)
    public function kardex()
    {
        return $this->hasMany(Kardex::class);
    }

    // ✅ Relación con capas de costo (FIFO)
    public function capasCostos()
    {
        return $this->hasMany(CapaCosto::class);
    }

    // Relaciones existentes
    public function proveedores()
    {
        return $this->hasMany(ArticuloProveedor::class, 'articulo_id');
    }

    public function proveedoresDirectos()
    {
        return $this->belongsToMany(
            \App\Models\Compras\Proveedor::class,
            'cmp_articulos_proveedores',
            'articulo_id',
            'proveedor_id'
        )->withPivot('codigo_proveedor', 'costo_compra', 'es_principal');
    }

    public function proveedorPrincipal()
    {
        return $this->hasOne(ArticuloProveedor::class, 'articulo_id')
            ->where('es_principal', true);
    }

    public function atributosDirectos()
    {
        return $this->belongsToMany(
            Atributo::class,
            'alm_articulos_atributos',
            'articulo_id',
            'atributo_id'
        )->withPivot('valor');
    }

    public function getPrecioByLista($listaPrecioId)
    {
        $precio = $this->precios()
            ->where('lista_precio_id', $listaPrecioId)
            ->first();
        return $precio?->precio ?? 0;
    }

    public function getPreciosConListas()
    {
        return $this->precios()
            ->with('listaPrecio')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->lista_precio_id => [
                    'nombre' => $item->listaPrecio?->nombre ?? 'Sin lista',
                    'precio' => $item->precio,
                    'moneda' => $item->listaPrecio?->moneda ?? 'BOB',
                ]
            ]);
    }

    public function grupoArticulo()
    {
        return $this->belongsTo(GrupoArticulo::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function codigosBarras()
    {
        return $this->hasMany(CodigoBarras::class);
    }

    public function almacenes()
    {
        return $this->hasMany(ArticuloAlmacen::class);
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }

    public function series()
    {
        return $this->hasMany(Serie::class);
    }

    public function precios()
    {
        return $this->hasMany(PrecioArticulo::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function imagenes()
    {
        return $this->hasMany(ArticuloImagen::class);
    }

    public function atributos()
    {
        return $this->hasMany(ArticuloAtributo::class);
    }

    public function unidades()
    {
        return $this->hasMany(ArticuloUnidad::class);
    }

    public function existencias()
    {
        return $this->hasMany(Existencia::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cotizacionesDetalle()
    {
        return $this->hasMany(CotizacionDetalle::class);
    }

    public function pedidosDetalle()
    {
        return $this->hasMany(PedidoDetalle::class);
    }

    public function facturasDetalle()
    {
        return $this->hasMany(FacturaDetalle::class);
    }

    // ========== SCOPES ==========

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInventariable($query)
    {
        return $query->where('inventariable', true);
    }

    public function scopeComprable($query)
    {
        return $query->where('comprable', true);
    }

    public function scopeVendible($query)
    {
        return $query->where('vendible', true);
    }

    // ========== ACCESORS ==========

    public function getNombreCompletoAttribute()
    {
        return $this->nombre_comercial ?? $this->descripcion ?? $this->codigo;
    }

    public function getComisionFormateadaAttribute()
    {
        return number_format($this->comision, 2) . '%';
    }
}