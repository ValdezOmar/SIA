<?php

namespace App\Models\Ventas\Concerns;

use App\Models\Sistema\Sucursal;
use App\Models\Ventas\Cliente;
use Illuminate\Validation\ValidationException;

trait ValidaContextoVenta
{
    public static function bootValidaContextoVenta(): void
    {
        static::saving(function ($venta): void {
            if ($venta->empresa_id && $venta->cliente_id) {
                $clienteValido = Cliente::withTrashed()
                    ->whereKey($venta->cliente_id)
                    ->where('empresa_id', $venta->empresa_id)
                    ->exists();

                if (! $clienteValido) {
                    throw ValidationException::withMessages([
                        'cliente_id' => 'El cliente seleccionado no pertenece a la empresa de esta venta.',
                    ]);
                }
            }

            if ($venta->empresa_id && $venta->sucursal_id) {
                $sucursalValida = Sucursal::query()
                    ->whereKey($venta->sucursal_id)
                    ->where('empresa_id', $venta->empresa_id)
                    ->exists();

                if (! $sucursalValida) {
                    throw ValidationException::withMessages([
                        'sucursal_id' => 'La sucursal seleccionada no pertenece a la empresa de esta venta.',
                    ]);
                }
            }
        });
    }
}
