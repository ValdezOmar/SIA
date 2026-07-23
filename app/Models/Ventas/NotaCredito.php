<?php

namespace App\Models\Ventas;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaCredito extends Model
{
    use SoftDeletes;

    protected $table = 'ven_notas_credito';  // ✅ Nombre correcto

    protected $guarded = [];

    protected $casts = [
        'fecha_emision' => 'date',
        'monto' => 'decimal:2',
    ];

    // ========== RELACIONES ==========

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // ========== MÉTODOS ==========

    public static function generarNumero()
    {
        $gestion = date('y');
        $prefijo = 'NC-' . $gestion;

        $ultimo = self::withTrashed()
            ->where('numero', 'LIKE', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->numero, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }
}