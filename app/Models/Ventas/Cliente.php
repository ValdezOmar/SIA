<?php

namespace App\Models\Ventas;

use App\Models\Inventario\ListaPrecio;
use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'ven_clientes';

    protected $guarded = [];

    protected $casts = [
        'descuento_general' => 'decimal:2',
        'descuento_especial' => 'decimal:2',
        'activo' => 'boolean',
        'bloqueado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->creado_por = Auth::id();
            }
            if (empty($model->codigo)) {
                $model->codigo = self::generarCodigo();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->actualizado_por = Auth::id();
            }
        });
    }

    // ========== RELACIONES ==========

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    // ✅ Agregar relación con facturas
    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    // ✅ Agregar relación con pagos
    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    // ✅ Agregar relación con notas de crédito
    public function notasCredito()
    {
        return $this->hasMany(NotaCredito::class);
    }

    // ========== SCOPES ==========

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBloqueado($query)
    {
        return $query->where('bloqueado', true);
    }

    public function scopeByCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('tipo_cliente', $tipo);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('codigo', 'like', "%{$search}%")
                ->orWhere('nombre', 'like', "%{$search}%")
                ->orWhere('razon_social', 'like', "%{$search}%")
                ->orWhere('ci/nit', 'like', "%{$search}%")
                ->orWhere('correo', 'like', "%{$search}%")
                ->orWhere('telefono', 'like', "%{$search}%");
        });
    }

    // ========== ACCESSORS ==========

    public function getNombreCompletoAttribute()
    {
        return $this->razon_social ?? $this->nombre;
    }

    public function getTipoLabelAttribute()
    {
        return match ($this->tipo_cliente) {
            'persona_natural' => 'Persona Natural',
            'empresa' => 'Empresa',
            'gobierno' => 'Gobierno',
            'extranjero' => 'Extranjero',
            default => $this->tipo_cliente,
        };
    }

    public function getCategoriaLabelAttribute()
    {
        return match ($this->categoria) {
            'regular' => 'Regular',
            'mayorista' => 'Mayorista',
            'minorista' => 'Minorista',
            'vip' => 'VIP',
            'revendedor' => 'Revendedor',
            default => $this->categoria,
        };
    }

    public function getCategoriaColorAttribute()
    {
        return match ($this->categoria) {
            'regular' => 'gray',
            'mayorista' => 'info',
            'minorista' => 'success',
            'vip' => 'warning',
            'revendedor' => 'primary',
            default => 'gray',
        };
    }

    public function getEstadoLabelAttribute()
    {
        if ($this->bloqueado) {
            return 'Bloqueado';
        }
        return $this->activo ? 'Activo' : 'Inactivo';
    }

    public function getEstadoColorAttribute()
    {
        if ($this->bloqueado) {
            return 'danger';
        }
        return $this->activo ? 'success' : 'gray';
    }

    // ========== MUTATORS ==========

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre'] = ucwords(strtolower($value));
    }

    public function setRazonSocialAttribute($value)
    {
        $this->attributes['razon_social'] = $value ? ucwords(strtolower($value)) : null;
    }

    public function setCorreoAttribute($value)
    {
        $this->attributes['correo'] = $value ? strtolower($value) : null;
    }

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $gestion = date('y');
        $prefijo = 'CLI-' . $gestion;

        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->codigo, -3)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo . str_pad($correlativo, 3, '0', STR_PAD_LEFT);
    }
}