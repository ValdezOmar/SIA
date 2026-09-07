<?php

namespace App\Services\Inventario;

use App\Models\Inventario\InventarioFisico;
use App\Models\Sistema\Empresa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventarioPdfService
{
    public function descargar(InventarioFisico $inventario): StreamedResponse
    {
        $contenido = $this->generar($inventario);

        return response()->streamDownload(fn () => print ($contenido), $inventario->codigo.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function generar(InventarioFisico $inventario): string
    {
        $user = auth()->user();
        abort_unless($user?->can(InventarioFisicoService::VER), 403);
        $inventario = InventarioFisico::delUsuario($user)->conProgreso()
            ->with(['empresa', 'sucursal', 'almacen', 'responsable',
                'conteos' => fn ($query) => $query->orderBy('codigo')->with(['contador', 'revisor']),
                'eventos' => fn ($query) => $query->orderBy('id')->with(['usuario', 'conteo']),
            ])->findOrFail($inventario->id);

        $pdf = Pdf::loadView('exports.inventario-pdf', [
            'inventario' => $inventario,
            'logoDataUri' => $this->logoDataUri($inventario->empresa),
            'generadoPor' => $user->name,
            'generadoEl' => now(),
        ])->setPaper('a4', 'landscape')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(36, $canvas->get_height() - 25, 'SIA · '.$inventario->codigo, $font, 8, [0.35, 0.4, 0.45]);
        $canvas->page_text($canvas->get_width() - 140, $canvas->get_height() - 25, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 8, [0.35, 0.4, 0.45]);

        return $pdf->output();
    }

    public function logoDataUri(?Empresa $empresa): ?string
    {
        $path = null;
        if ($empresa?->logo_path) {
            $root = realpath(Storage::disk('public')->path(''));
            $candidate = realpath(Storage::disk('public')->path($empresa->logo_path));
            if ($root && $candidate && str_starts_with(strtolower(str_replace('\\', '/', $candidate)), strtolower(str_replace('\\', '/', $root)).'/')) {
                $path = $candidate;
            }
        }
        foreach (array_filter([$path, public_path('images/logo.png')]) as $file) {
            if (! is_file($file) || ! is_readable($file) || filesize($file) > 2 * 1024 * 1024) {
                continue;
            }
            $mime = mime_content_type($file);
            if (in_array($mime, ['image/png', 'image/jpeg'], true)) {
                return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($file));
            }
        }

        return null;
    }
}
