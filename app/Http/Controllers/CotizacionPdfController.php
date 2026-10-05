<?php

namespace App\Http\Controllers;

use App\Models\Ventas\Cotizacion;
use App\Services\Ventas\CotizacionPdfService;
use Illuminate\Http\Response;

class CotizacionPdfController extends Controller
{
    public function __invoke(Cotizacion $cotizacion, CotizacionPdfService $pdf): Response
    {
        $contenido = $pdf->generar($cotizacion);
        $nombre = 'Proforma-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $cotizacion->codigo).'.pdf';

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
            'Content-Length' => (string) strlen($contenido),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
