<?php

namespace Tests\Feature;

use App\Support\ClienteSelectOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClienteSelectOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function empresa(string $nombre): int
    {
        return DB::table('conf_empresas')->insertGetId([
            'razon_social' => $nombre, 'nombre_comercial' => $nombre,
            'pais' => 'Bolivia', 'empresa_activo' => true,
        ]);
    }

    private function cliente(int $empresa, array $datos = []): int
    {
        return DB::table('ven_clientes')->insertGetId(array_merge([
            'empresa_id' => $empresa, 'codigo' => uniqid('CLI-'),
            'nombre' => 'JUAN QUISPE', 'celular' => '+591 7123-4567',
            'telefono' => '(591) 2-234.5678', 'activo' => true,
        ], $datos));
    }

    public function test_busca_contactos_con_distintos_formatos_y_respeta_empresa_y_estado(): void
    {
        $empresa = $this->empresa('Ventas');
        $id = $this->cliente($empresa, ['codigo' => '26001']);
        $otro = $this->cliente($this->empresa('Otra'));
        $this->cliente($empresa, ['activo' => false]);
        $this->cliente($empresa, ['deleted_at' => now()]);

        foreach (['JUAN', '26001', '71234567', '7123-4567', '22345678', '+591 (2) 234-5678'] as $busqueda) {
            $this->assertSame([$id => 'JUAN QUISPE · +591 7123-4567'], ClienteSelectOptions::ventas($empresa, $busqueda));
        }

        $this->assertNull(ClienteSelectOptions::seleccionado($otro, $empresa));
    }

    public function test_busqueda_y_etiqueta_recuperan_clientes_fuera_de_las_primeras_opciones(): void
    {
        $empresa = $this->empresa('Ventas');
        for ($i = 0; $i < 51; $i++) {
            $this->cliente($empresa, ['nombre' => 'ANA '.$i]);
        }
        $id = $this->cliente($empresa, ['nombre' => 'ZULMA', 'celular' => '79998888']);

        $this->assertCount(50, ClienteSelectOptions::ventas($empresa));
        $this->assertArrayNotHasKey($id, ClienteSelectOptions::ventas($empresa));
        $this->assertSame([$id => 'ZULMA · 79998888'], ClienteSelectOptions::ventas($empresa, '79998888'));
        $this->assertSame('ZULMA · 79998888', ClienteSelectOptions::seleccionado($id, $empresa));
    }

    public function test_clientes_sin_celular_muestran_telefono_o_solo_nombre(): void
    {
        $empresa = $this->empresa('Ventas');
        $id = $this->cliente($empresa, ['celular' => null]);
        $sinContacto = $this->cliente($empresa, ['celular' => null, 'telefono' => null]);

        $this->assertSame('JUAN QUISPE · (591) 2-234.5678', ClienteSelectOptions::seleccionado($id, $empresa));
        $this->assertSame('JUAN QUISPE', ClienteSelectOptions::seleccionado($sinContacto, $empresa));
    }
}
