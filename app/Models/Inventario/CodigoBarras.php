<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class CodigoBarras extends Model
{
    protected $table = 'alm_codigos_barras';

    protected $guarded = [];

    protected $casts = [
        'principal' => 'boolean',
    ];

    // ========== RELACIONES ==========

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    // ========== SCOPES ==========

    public function scopePrincipal($query)
    {
        return $query->where('principal', true);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // ========== ACCESORS ==========

    public function getLongitudAttribute()
    {
        return strlen($this->codigo_barras);
    }

    public function getTipoSugeridoAttribute()
    {
        $longitud = $this->longitud;
        
        return match($longitud) {
            13 => 'EAN-13',
            8 => 'EAN-8',
            12 => 'UPC-A',
            default => 'Desconocido',
        };
    }

    // ========== MÉTODOS ==========

    public static function getTiposDisponibles()
    {
        return [
            'EAN-13' => 'EAN-13',
            'EAN-8' => 'EAN-8',
            'UPC-A' => 'UPC-A',
            'UPC-E' => 'UPC-E',
            'CODE-128' => 'CODE-128',
            'CODE-39' => 'CODE-39',
            'QR' => 'QR',
            'PDF417' => 'PDF417',
            'DataMatrix' => 'DataMatrix',
            'Interno' => 'Interno',
            'Otro' => 'Otro',
        ];
    }

    /**
     * Validar si un código de barras tiene un formato válido según su tipo
     */
    public static function validarFormato($codigo, $tipo)
    {
        $codigo = preg_replace('/[^0-9]/', '', $codigo);
        
        return match($tipo) {
            'EAN-13' => strlen($codigo) === 13,
            'EAN-8' => strlen($codigo) === 8,
            'UPC-A' => strlen($codigo) === 12,
            'UPC-E' => strlen($codigo) === 6 || strlen($codigo) === 8,
            default => true,
        };
    }
}