<?php
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PreviewBladeEnvironment
{
    public function addLoop(mixed $data): void {}
    public function incrementLoopIndices(): void {}
    public function popLoop(): void {}
    public function getLastLoop(): object { return (object) ['iteration' => 1, 'index' => 0, 'first' => true, 'last' => false, 'count' => 36, 'remaining' => 35, 'depth' => 1, 'parent' => null]; }
}

$compiler = new BladeCompiler(new Filesystem(), sys_get_temp_dir() . '/sia-blade-preview-cache');
$render = function (string $path) use ($compiler): string {
    $blade = file_get_contents($path);
    $compiled = $compiler->compileString($blade);
    $compiledPath = tempnam(sys_get_temp_dir(), 'sia-blade-');
    file_put_contents($compiledPath, $compiled);
    $include = function (string $__path, array $__data): string {
        extract($__data, EXTR_SKIP);
        $__env = new PreviewBladeEnvironment();
        ob_start();
        include $__path;
        return ob_get_clean();
    };
    try {
        return $include($compiledPath, [
            'cotizacion' => (object) [
                'codigo' => 'COT-DEMO-001',
                'moneda' => 'BOB',
                'fecha_emision' => Carbon::parse('2026-10-12'),
                'fecha_validez' => Carbon::parse('2026-10-19'),
                'observaciones' => 'Precios sujetos a disponibilidad. Entrega coordinada con el cliente.',
                'empresa' => (object) ['nombre_comercial' => 'TecnoConfort Bolivia', 'razon_social' => 'TecnoConfort Bolivia SRL', 'nit' => '3380240013', 'telefono' => '78496884', 'celular' => '78868486', 'email' => 'contacto@example.com'],
                'sucursal' => (object) ['nombre' => 'Sucursal Central'],
                'cliente' => (object) ['nombre' => 'FH Bolivia', 'celular' => '67600815', 'telefono' => null],
                'detalles' => new Collection(array_map(fn (int $i) => (object) [
                    'id' => $i,
                    'descripcion_articulo' => 'Producto de demostración número ' . $i . ' con descripción comercial amplia para probar el ajuste de líneas y paginación del documento.',
                    'articulo' => (object) ['nombre_comercial' => 'Producto ' . $i],
                    'descuento' => $i % 4 === 0 ? 12.50 : 0,
                    'impuesto' => 0,
                    'cantidad' => $i % 3 + 1,
                    'precio_unitario' => 360 + $i,
                    'total' => ($i % 3 + 1) * (360 + $i),
                    'subtotal' => ($i % 3 + 1) * (360 + $i),
                ], range(1, 36))),
            ],
            'logoDataUri' => null,
            'fotos' => [],
        ]);
    } finally {
        @unlink($compiledPath);
    }
};

foreach ([
    'before' => __DIR__ . '/cotizacion-pdf-before.blade.php',
    'after' => dirname(__DIR__, 2) . '/resources/views/exports/cotizacion-pdf.blade.php',
] as $label => $path) {
    $html = $render($path);
    file_put_contents(__DIR__ . '/cotizacion-' . $label . '.html', $html);
    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $pdf = new Dompdf($options);
    $pdf->loadHtml($html, 'UTF-8');
    $pdf->setPaper('a4');
    $pdf->render();
    file_put_contents(__DIR__ . '/cotizacion-' . $label . '.pdf', $pdf->output());
    echo $label . ': ' . $pdf->getCanvas()->get_page_count() . " páginas\n";
}

