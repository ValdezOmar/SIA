<?php

namespace App\Models\Inventario;

use App\Models\Compras\ArticuloProveedor;
use App\Models\Sistema\Empresa;
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
        'costo_referencial',
        'precio_base',
        'comision',
        'foto_catalogo',
        'documentacion_tecnica',
        'activo',
        'empresa_id'
    ];

    protected $attributes = [
        'costo_referencial' => 0,
        'precio_base' => 0,
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
        'costo_referencial' => 'decimal:6',
        'precio_base' => 'decimal:6',
        'comision' => 'decimal:6',
    ];

    // Relación con proveedores a través de la tabla pivote
    public function proveedores()
    {
        return $this->hasMany(ArticuloProveedor::class, 'articulo_id');
    }

    // Relación directa con proveedores (opcional)
    public function proveedoresDirectos()
    {
        return $this->belongsToMany(
            \App\Models\Compras\Proveedor::class,
            'cmp_articulos_proveedores',
            'articulo_id',
            'proveedor_id'
        )->withPivot('codigo_proveedor', 'costo_compra', 'es_principal');
    }

    // Obtener proveedor principal
    public function proveedorPrincipal()
    {
        return $this->hasOne(ArticuloProveedor::class, 'articulo_id')
            ->where('es_principal', true);
    }

    // Relación directa con los atributos (opcional)
    public function atributosDirectos()
    {
        return $this->belongsToMany(
            Atributo::class,
            'alm_articulos_atributos',
            'articulo_id',
            'atributo_id'
        )->withPivot('valor');
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

    // Scopes útiles
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

    // Accesores
    public function getNombreCompletoAttribute()
    {
        return $this->nombre_comercial ?? $this->descripcion ?? $this->codigo;
    }

    public function getCostoReferencialFormateadoAttribute()
    {
        return '$ ' . number_format($this->costo_referencial, 2);
    }

    public function getPrecioBaseFormateadoAttribute()
    {
        return '$ ' . number_format($this->precio_base, 2);
    }
}