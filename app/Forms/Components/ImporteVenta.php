<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Placeholder;

class ImporteVenta extends Placeholder
{
    public function getExtraAttributes(): array
    {
        $ruta = $this->getContainer()->getStatePath();
        $campo = $this->getName();
        $mapa = ['subtotal_linea' => 'subtotal', 'impuesto_linea' => 'impuesto', 'total_con_iva' => 'total'];
        if (isset($mapa[$campo])) {
            $valor = '$wire.$get('.json_encode($ruta.'.'.$mapa[$campo]).')';
            $raiz = preg_replace('/\.detalles\.[^.]+$/', '', $ruta);
        } else {
            $raiz = $ruta;
            $valor = 'window.siaVentasImportes.sumar($wire.$get('.json_encode($raiz.'.detalles').'), '
                .json_encode($campo).', $wire.$get('.json_encode($raiz.'.costo_envio').'), $wire.$get('.json_encode($raiz.'.monto_pagado').'))';
        }

        return array_merge(parent::getExtraAttributes(), [
            'x-text' => 'window.siaVentasImportes.formato('.$valor.', $wire.$get('.json_encode($raiz.'.moneda').') || "BOB")',
        ]);
    }
}
