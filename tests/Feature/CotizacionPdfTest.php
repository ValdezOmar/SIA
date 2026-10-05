<?php

namespace Tests\Feature;

use App\Filament\Resources\Ventas\CotizacionResource\Pages\ListCotizaciones;
use App\Models\Inventario\Articulo;
use App\Models\Sistema\Parametro;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Cotizacion;
use App\Services\Ventas\CotizacionPdfService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CotizacionPdfTest extends TestCase
{
    use RefreshDatabase;

    private Cotizacion $cotizacion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
        $empresa = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Empresa de demostración', 'nombre_comercial' => 'Empresa de demostración', 'pais' => 'Bolivia', 'nit' => '123456789', 'telefono' => '70000000', 'email' => 'contacto@example.com', 'empresa_activo' => true]);
        $sucursal = Sucursal::create(['empresa_id' => $empresa, 'nombre' => 'Sucursal Central', 'activo' => true]);
        $cliente = Cliente::create(['codigo' => 'CLI-PDF', 'nombre' => 'Cliente de demostración', 'celular' => '71111111', 'empresa_id' => $empresa]);
        $articulo = Articulo::create(['codigo' => 'ART-PDF', 'nombre_comercial' => 'Producto', 'empresa_id' => $empresa, 'inventariable' => true]);
        $this->cotizacion = Cotizacion::create(['codigo' => 'COT-00148', 'cliente_id' => $cliente->id, 'empresa_id' => $empresa, 'sucursal_id' => $sucursal->id, 'fecha_emision' => '2026-09-17', 'fecha_validez' => '2026-09-24', 'moneda' => 'BOB', 'observaciones' => "Entrega previa coordinación.\nGarantía según condiciones del fabricante."]);
        foreach ([['Soporte con ventosas para Starlink Mini', 1, 406], ['Mochila para Starlink Mini', 1, 1276], ['Batería portátil 100W con salida USB C PD', 1, 1844], ['Cable tipo C DC para Starlink Mini de 3 mts', 5, 174], ['Kit completo de Starlink Mini', 1, 2768], ['Funda protectora de silicona para Starlink Mini', 1, 522]] as [$descripcion, $cantidad, $precio]) {
            $this->cotizacion->detalles()->create(['articulo_id' => $articulo->id, 'codigo_articulo' => $articulo->codigo, 'descripcion_articulo' => $descripcion, 'cantidad' => $cantidad, 'precio_unitario' => $precio]);
        }
    }

    public function test_pdf_se_genera_sin_modificar_la_cotizacion(): void
    {
        $antes = $this->cotizacion->fresh()->getAttributes();
        $pdf = app(CotizacionPdfService::class)->generar($this->cotizacion);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame($antes, $this->cotizacion->fresh()->getAttributes());
        $html = view('exports.cotizacion-pdf', ['cotizacion' => $this->cotizacion->fresh()->load(['empresa', 'cliente', 'detalles.articulo']), 'logoDataUri' => null])->render();
        $this->assertStringContainsString('7.686,00', $html);
        $this->assertStringContainsString('24/09/2026', $html);
        $this->assertStringContainsString('Vencimiento de la cotización:', $html);
        $this->assertStringContainsString('Entrega previa coordinación.', $html);
        $this->assertStringContainsString('Sucursal Central', $html);
        $this->assertStringNotContainsString('Nota: Los precios', $html);
        if (getenv('SIA_PDF_PREVIEW')) {
            @mkdir(base_path('tmp/pdfs'), 0777, true);
            file_put_contents(base_path('tmp/pdfs/proforma-demo.pdf'), $pdf);
        }
    }

    public function test_descarga_pdf_con_nombre_de_proforma(): void
    {
        $this->get(route('cotizaciones.pdf', $this->cotizacion))
            ->assertOk()
            ->assertDownload('Proforma-COT-00148.pdf');
    }

    public function test_logo_configurado_en_parametros_prevalece(): void
    {
        $ruta = public_path('images/logo-test-proforma.png');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        file_put_contents($ruta, $png);
        try {
            Parametro::query()->firstOrCreate([])->update(['logo_path' => '/images/logo-test-proforma.png']);
            $this->assertSame('data:image/png;base64,'.base64_encode($png), app(CotizacionPdfService::class)->logoDataUri());
        } finally {
            unlink($ruta);
        }
    }

    public function test_pdf_para_compartir_requiere_sesion_y_respeta_empresa(): void
    {
        $url = route('cotizaciones.pdf', $this->cotizacion);
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertHeader('Content-Disposition', 'attachment; filename="Proforma-COT-00148.pdf"')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame((string) strlen($response->getContent()), $response->headers->get('Content-Length'));
        $usuario = \Mockery::mock(User::class)->makePartial();
        $usuario->setTable('users');
        $usuario->exists = true;
        $usuario->setRawAttributes(auth()->user()->getAttributes());
        $usuario->shouldReceive('getEmpresaIdAttribute')->andReturn(999999);
        $this->actingAs($usuario);
        try {
            app(CotizacionPdfService::class)->generar($this->cotizacion);
            $this->fail('No se debe compartir una cotización de otra empresa.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertSame(Cotizacion::class, $exception->getModel());
        }
        auth()->logout();
        $this->getJson($url)->assertUnauthorized();
    }

    public function test_accion_compartir_abre_modal_sin_cambiar_estado(): void
    {
        $antes = $this->cotizacion->fresh()->getAttributes();
        $componente = Livewire::test(ListCotizaciones::class)
            ->mountTableAction('compartir_whatsapp', $this->cotizacion);
        $this->assertSame('compartir_whatsapp', $componente->instance()->getMountedAction()?->getName());
        $html = $componente->instance()->getMountedAction()->getModalContent()->render();
        $this->assertStringContainsString('Compartir PDF', $html);
        $this->assertStringContainsString('Abrir chat de WhatsApp', $html);
        $this->assertStringContainsString('x-on:click="descargar()"', $html);
        $this->assertStringContainsString('enlace.download = this.archivo.name', $html);
        $this->assertStringNotContainsString('href="'.route('cotizaciones.pdf', $this->cotizacion).'"', $html);
        $this->assertSame($antes, $this->cotizacion->fresh()->getAttributes());
    }

    public function test_exportacion_sin_autenticacion_es_rechazada(): void
    {
        auth()->logout();
        try {
            app(CotizacionPdfService::class)->generar($this->cotizacion);
            $this->fail('No se debe exportar sin autenticación.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_pdf_largo_y_totales_con_descuento_e_impuesto(): void
    {
        $detalle = $this->cotizacion->detalles()->first();
        $detalle->update(['descuento' => 46, 'subtotal' => 360, 'impuesto' => 46.80, 'total' => 406.80]);
        $html = view('exports.cotizacion-pdf', ['cotizacion' => $this->cotizacion->fresh()->load(['empresa', 'cliente', 'detalles.articulo']), 'logoDataUri' => null])->render();
        $this->assertStringContainsString('7.686,80', $html);
        $this->assertMatchesRegularExpression('/Subtotal \(BOB\).*?7\.686,00/s', $html);
        $this->assertMatchesRegularExpression('/Impuesto \(BOB\).*?46,80/s', $html);
        $this->assertMatchesRegularExpression('/Descuento \(BOB\).*?46,00/s', $html);
        for ($i = 0; $i < 24; $i++) {
            $copia = $detalle->replicate();
            $copia->linea = $i + 7;
            $copia->descripcion_articulo = 'Equipo con descripción extensa para verificar ajuste de texto y continuidad de la tabla en páginas adicionales. Incluye accesorios y garantía según las condiciones de la cotización.';
            $copia->save();
        }
        $pdf = app(CotizacionPdfService::class)->generar($this->cotizacion);
        $this->assertStringStartsWith('%PDF-', $pdf);
        if (getenv('SIA_PDF_PREVIEW')) {
            file_put_contents(base_path('tmp/pdfs/proforma-larga.pdf'), $pdf);
        }
    }

    public function test_foto_de_catalogo_se_incluye_y_archivos_ausentes_no_rompen_pdf(): void
    {
        Storage::fake('public');
        $imagen = imagecreatetruecolor(80, 80);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 245, 246, 250));
        imagefilledrectangle($imagen, 14, 20, 66, 56, imagecolorallocate($imagen, 11, 45, 80));
        imagefilledrectangle($imagen, 25, 56, 55, 62, imagecolorallocate($imagen, 90, 100, 115));
        ob_start();
        imagepng($imagen);
        $contenido = ob_get_clean();
        imagedestroy($imagen);
        Storage::disk('public')->put('articulos/catalogo/prueba.png', $contenido);
        $this->cotizacion->detalles()->first()->articulo->update(['foto_catalogo' => 'articulos/catalogo/prueba.png']);
        $servicio = app(CotizacionPdfService::class);
        $this->assertSame('data:image/png;base64,'.base64_encode($contenido), $servicio->fotoDataUri('articulos/catalogo/prueba.png'));
        $this->assertNull($servicio->fotoDataUri('articulos/catalogo/no-existe.png'));
        $this->assertNull($servicio->fotoDataUri('../../../../.env'));
        $pdf = $servicio->generar($this->cotizacion);
        $this->assertStringStartsWith('%PDF-', $pdf);
        if (getenv('SIA_PDF_PREVIEW')) {
            file_put_contents(base_path('tmp/pdfs/proforma-fotos.pdf'), $pdf);
        }
    }
}
