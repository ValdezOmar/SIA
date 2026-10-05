<?php

namespace App\Services\Ventas;

use App\Filament\Resources\Ventas\CotizacionResource;
use App\Models\Sistema\Parametro;
use App\Models\Ventas\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CotizacionPdfService
{
    public function descargar(Cotizacion $cotizacion): StreamedResponse
    {
        $contenido = $this->generar($cotizacion);
        $nombre = preg_replace('/[^A-Za-z0-9_-]/', '-', $cotizacion->codigo);

        return response()->streamDownload(fn () => print ($contenido), 'Proforma-'.$nombre.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function generar(Cotizacion $cotizacion): string
    {
        abort_unless(auth()->check(), 403);
        $cotizacion = CotizacionResource::getEloquentQuery()->with(['empresa', 'sucursal', 'cliente', 'detalles.articulo'])->findOrFail($cotizacion->id);
        abort_unless(CotizacionResource::canView($cotizacion), 403);

        return Pdf::loadView('exports.cotizacion-pdf', [
            'cotizacion' => $cotizacion,
            'logoDataUri' => $this->logoDataUri(),
            'fotos' => $cotizacion->detalles->mapWithKeys(fn ($detalle) => [$detalle->id => $this->fotoDataUri($detalle->articulo?->foto_catalogo)])->all(),
        ])->setPaper('a4')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false])->output();
    }

    public function logoDataUri(): ?string
    {
        $root = realpath(public_path('images'));
        $configurado = Parametro::query()->value('logo_path');
        foreach (array_unique(array_filter([$configurado, '/images/logo.png'])) as $ruta) {
            if (! is_string($ruta) || ! str_starts_with($ruta, '/images/')) {
                continue;
            }
            $archivo = realpath(public_path(ltrim($ruta, '/')));
            if (! $root || ! $archivo || ! str_starts_with(strtolower(str_replace('\\', '/', $archivo)), strtolower(str_replace('\\', '/', $root)).'/') || ! is_file($archivo) || ! is_readable($archivo) || filesize($archivo) > 2 * 1024 * 1024) {
                continue;
            }
            $mime = mime_content_type($archivo);
            if (in_array($mime, ['image/png', 'image/jpeg', 'image/svg+xml'], true)) {
                return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($archivo));
            }
        }

        return null;
    }

    public function fotoDataUri(?string $ruta): ?string
    {
        if (! $ruta || str_contains($ruta, ':') || str_starts_with(str_replace('\\', '/', $ruta), '/') || in_array('..', explode('/', str_replace('\\', '/', $ruta)), true)) {
            return null;
        }
        $root = realpath(Storage::disk('public')->path(''));
        $archivo = realpath(Storage::disk('public')->path($ruta));
        if (! $root || ! $archivo || ! str_starts_with(strtolower(str_replace('\\', '/', $archivo)), strtolower(str_replace('\\', '/', $root)).'/') || ! is_file($archivo) || ! is_readable($archivo) || filesize($archivo) > 5 * 1024 * 1024) {
            return null;
        }
        $mime = mime_content_type($archivo);

        return in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)
            ? 'data:'.$mime.';base64,'.base64_encode(file_get_contents($archivo))
            : null;
    }
}
