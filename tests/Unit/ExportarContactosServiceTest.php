<?php

namespace Tests\Unit;

use App\Services\Ventas\ExportarContactosService;
use PHPUnit\Framework\TestCase;

class ExportarContactosServiceTest extends TestCase
{
    public function test_exporta_nombre_y_numero_internacional_sin_repetir_celulares(): void
    {
        $servicio = new ExportarContactosService;
        $clientes = [
            ['codigo' => '26001', 'nombre' => 'Juan Quispe', 'celular' => '7000 1234'],
            ['codigo' => '26002', 'nombre' => 'Duplicado', 'celular' => '+59170001234'],
            ['codigo' => '26003', 'nombre' => 'Sin celular', 'celular' => null],
        ];
        $vcf = $servicio->generar($clientes);

        $this->assertStringContainsString("FN:26001 JUAN QUISPE\r\n", $vcf);
        $this->assertStringContainsString("TEL;TYPE=CELL:+59170001234\r\n", $vcf);
        $this->assertSame(1, substr_count($vcf, 'BEGIN:VCARD'));
        $this->assertSame($vcf, $servicio->generar($clientes));
        $this->assertSame('', $servicio->generar($clientes, $vcf));
    }

    public function test_excluye_contactos_importados_y_exporta_solo_nuevos(): void
    {
        $anteriores = "BEGIN:VCARD\r\nVERSION:3.0\r\nTEL;TYPE=CELL:0059170001234\r\nEND:VCARD\r\n";
        $vcf = (new ExportarContactosService)->generar([
            ['codigo' => '26001', 'nombre' => 'Juan', 'celular' => '70001234'],
            ['codigo' => '26002', 'nombre' => 'Ana', 'celular' => '+5491123456789'],
        ], $anteriores);

        $this->assertStringNotContainsString('26001', $vcf);
        $this->assertStringContainsString('FN:26002 ANA', $vcf);
        $this->assertStringContainsString('TEL;TYPE=CELL:+5491123456789', $vcf);
    }

    public function test_escapa_texto_y_pliega_lineas_sin_romper_utf8(): void
    {
        $vcf = (new ExportarContactosService)->generar([
            ['codigo' => '26001', 'nombre' => "Peña; Pérez, Juan\n".str_repeat('Á', 100), 'celular' => '70001234'],
        ]);

        $this->assertTrue(mb_check_encoding($vcf, 'UTF-8'));
        foreach (explode("\r\n", $vcf) as $linea) {
            $this->assertLessThanOrEqual(75, strlen($linea));
        }
        $this->assertStringContainsString('PEÑA\\; PÉREZ\\, JUAN\\n', $vcf);
    }
}
