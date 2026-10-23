<?php

namespace Tests\Unit;

use App\Models\Inventario\Existencia;
use PHPUnit\Framework\TestCase;

class ExistenciaStockDisponibleTest extends TestCase
{
    public function test_calcula_stock_disponible_restandole_las_unidades_comprometidas(): void
    {
        $existencia = new Existencia([
            'cantidad_disponible' => 12,
            'cantidad_comprometida' => 5,
        ]);

        $this->assertSame(7.0, $existencia->stock_disponible);
    }
}
