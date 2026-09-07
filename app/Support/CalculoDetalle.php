<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class CalculoDetalle
{
    public static function calcular(array $fila, ?string $tipo, string $ruta = 'detalles'): array
    {
        if ($tipo === 'contabilidad') {
            if ((float) ($fila['debe'] ?? 0) > 0 && (float) ($fila['haber'] ?? 0) > 0) {
                throw ValidationException::withMessages([$ruta.'.debe' => 'Use Debe o Haber en cada partida, no ambos.']);
            }

            return $fila;
        }

        if ($tipo === 'recepcion') {
            $fila['costo_total'] = round((float) ($fila['cantidad_aceptada'] ?? 0) * (float) ($fila['costo_unitario'] ?? 0), 6);

            return $fila;
        }

        if ($tipo === 'solicitud') {
            $fila['subtotal'] = round((float) ($fila['cantidad'] ?? 0) * (float) ($fila['precio_estimado'] ?? 0), 6);

            return $fila;
        }

        if (! in_array($tipo, ['venta', 'compra'], true)) {
            return $fila;
        }

        $base = round((float) ($fila['cantidad'] ?? 0) * (float) ($fila['precio_unitario'] ?? 0), 6);
        $porcentaje = (float) ($fila['descuento_porcentaje'] ?? 0);
        $descuento = ($fila['_descuento_tipo'] ?? 'importe') === 'porcentaje'
            ? round($base * $porcentaje / 100, 6)
            : (float) ($fila['descuento'] ?? 0);

        if ($descuento < 0 || $descuento > $base || $porcentaje < 0 || $porcentaje > 100) {
            throw ValidationException::withMessages([$ruta.'.descuento' => 'El descuento debe estar entre cero y el importe de la línea (0 a 100 %).']);
        }

        $fila['descuento'] = $descuento;
        if ($tipo === 'venta') {
            $fila['descuento_porcentaje'] = $base > 0 ? round($descuento / $base * 100, 6) : 0;
        }
        $fila['subtotal'] = round($base - $descuento, 6);
        $tasa = $tipo === 'compra' || ($fila['aplicar_iva'] ?? false) ? 13 : 0;
        $fila['impuesto'] = round($fila['subtotal'] * $tasa / 100, 6);
        $fila['total'] = round($fila['subtotal'] + $fila['impuesto'], 6);

        return $fila;
    }
}
