<?php

namespace App\Services\Ventas;

class ExportarContactosService
{
    public function generar(iterable $clientes, string $contactosAnteriores = ''): string
    {
        $vistos = [];
        $contenido = '';
        $contactosAnteriores = preg_replace('/\r?\n[ \t]/', '', $contactosAnteriores);
        preg_match_all('/^TEL(?:;[^:\r\n]*)?:(.*)$/mi', $contactosAnteriores, $telefonos);
        foreach ($telefonos[1] as $telefono) {
            $numero = $this->normalizarTelefono($telefono);
            if ($numero !== null) {
                $vistos[$numero] = true;
            }
        }

        foreach ($clientes as $cliente) {
            $numero = $this->normalizarTelefono($cliente['celular'] ?? null);
            if ($numero === null || isset($vistos[$numero])) {
                continue;
            }
            $vistos[$numero] = true;
            $nombre = mb_strtoupper(trim(($cliente['codigo'] ?? '').' '.($cliente['nombre'] ?? '')), 'UTF-8');
            $nombre = str_replace(["\\", "\r\n", "\r", "\n", ';', ','], ['\\\\', '\\n', '\\n', '\\n', '\\;', '\\,'], $nombre);
            $lineas = [
                'BEGIN:VCARD',
                'VERSION:3.0',
                'UID:urn:sha256:'.hash('sha256', 'sia-contacto:'.$numero),
                'FN:'.$nombre,
                'N:'.$nombre.';;;;',
                'TEL;TYPE=CELL:'.$numero,
                'END:VCARD',
            ];
            foreach ($lineas as $linea) {
                while (strlen($linea) > 75) {
                    $parte = mb_strcut($linea, 0, 75, 'UTF-8');
                    $contenido .= $parte."\r\n";
                    $linea = ' '.substr($linea, strlen($parte));
                }
                $contenido .= $linea."\r\n";
            }
        }

        return $contenido;
    }

    private function normalizarTelefono(?string $telefono): ?string
    {
        $numero = preg_replace('/\D+/', '', $telefono ?? '');
        if (str_starts_with($numero, '00')) {
            $numero = substr($numero, 2);
        }
        if (strlen($numero) === 8) {
            $numero = '591'.$numero;
        }

        return strlen($numero) >= 8 && strlen($numero) <= 15 ? '+'.$numero : null;
    }
}
