<?php

namespace Tests\Unit;

use App\Services\Sistema\NotificacionExcepcionOperativaService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class NotificacionExcepcionOperativaServiceTest extends TestCase
{
    public function test_describe_el_problema_y_la_solucion_para_un_almacen_inactivo(): void
    {
        $servicio = new NotificacionExcepcionOperativaService;
        $contenido = $servicio->contenido(new RuntimeException('No existe un almacén activo para reservar los productos.'));

        $this->assertSame('No hay un almacén disponible', $contenido['titulo']);
        $this->assertStringContainsString('No existe un almacén activo', $contenido['mensaje']);
        $this->assertStringContainsString('Cree o active un almacén', $contenido['solucion']);
    }

    public function test_no_clasifica_un_error_tecnico_generico_como_operativo(): void
    {
        $servicio = new NotificacionExcepcionOperativaService;

        $this->assertFalse($servicio->esOperativa(new \Error('Fallo de programación')));
    }
}
