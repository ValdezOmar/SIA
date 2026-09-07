<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Placeholder;
use Illuminate\View\ComponentAttributeBag;

class ImporteVenta extends Placeholder
{
    public function getExtraAttributes(): array
    {
        $ruta = $this->getContainer()->getStatePath();
        $campo = $this->getName();
        $mapa = ['subtotal_linea' => 'subtotal', 'impuesto_linea' => 'impuesto', 'total_con_iva' => 'total'];
        if (isset($mapa[$campo])) {
            $valor = 'window.siaVentasImportes.calcular($wire.$get('.json_encode($ruta).') || {})['.json_encode($mapa[$campo]).']';
            $raiz = preg_replace('/\.detalles\.[^.]+$/', '', $ruta);
        } else {
            $raiz = $ruta;
            $valor = 'window.siaVentasImportes.sumar($wire.$get('.json_encode($raiz.'.detalles').'), '
                .json_encode($campo).', $wire.$get('.json_encode($raiz.'.costo_envio').'), $wire.$get('.json_encode($raiz.'.monto_pagado').'))';
        }

        return (new ComponentAttributeBag(parent::getExtraAttributes()))->merge([
            'x-text' => 'window.siaVentasImportes.formato('.$valor.', $wire.$get('.json_encode($raiz.'.moneda').') || "BOB")',
        ])->getAttributes();
    }
}
