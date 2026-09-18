<?php

namespace Tests\Feature;

use App\Filament\Resources\Compras\FacturaCompraResource\RelationManagers\PagosProveedorRelationManager;
use Tests\TestCase;

class PagosProveedorRelationManagerTest extends TestCase
{
    public function test_cuenta_respaldos_en_formatos_actuales_y_legados(): void
    {
        $this->assertSame(0, PagosProveedorRelationManager::contarRespaldos(null));
        $this->assertSame(1, PagosProveedorRelationManager::contarRespaldos('sistema/registro-automatico'));
        $this->assertSame(2, PagosProveedorRelationManager::contarRespaldos(['uno.pdf', 'dos.pdf']));
        $this->assertSame(2, PagosProveedorRelationManager::contarRespaldos('["uno.pdf", "dos.pdf"]'));
    }
}
