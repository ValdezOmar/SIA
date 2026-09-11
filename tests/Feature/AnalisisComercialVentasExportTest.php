<?php

namespace Tests\Feature;

use App\Exports\AnalisisComercialVentasExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalisisComercialVentasExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_uses_the_requested_sales_columns_and_executes_the_accounted_sales_query(): void
    {
        $export = new AnalisisComercialVentasExport(now()->format('Y-m'), null);

        $this->assertSame([
            'FECHA', 'CÓDIGO', 'NOMBRE', 'TOTAL UNIDADES', 'PRECIO UNITARIO',
            'DESCUENTO', 'TOTAL A PAGAR', 'COSTO', 'UTILIDAD', 'MÉTODO DE PAGO',
            'NO. RECIBO', 'CLIENTE', 'NÚMERO CELULAR',
        ], $export->headings());
        $this->assertCount(0, $export->collection());
    }
}
