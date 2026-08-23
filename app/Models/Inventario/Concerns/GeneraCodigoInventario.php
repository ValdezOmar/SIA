<?php

namespace App\Models\Inventario\Concerns;

use Illuminate\Support\Str;

trait GeneraCodigoInventario
{
    protected static function codigoCorrelativo(string $prefijo, int $digitos = 3): string
    {
        $ultimoCodigo = static::query()
            ->where('codigo', 'like', $prefijo.'-%')
            ->orderByDesc('codigo')
            ->value('codigo');

        $numero = $ultimoCodigo && preg_match('/-(\d+)$/', $ultimoCodigo, $coincidencias)
            ? (int) $coincidencias[1] + 1
            : 1;

        return $prefijo.'-'.str_pad((string) $numero, $digitos, '0', STR_PAD_LEFT);
    }

    protected static function codigoDosIniciales(string $nombre): string
    {
        $nombre = trim(Str::ascii($nombre));
        $palabras = array_values(array_filter(preg_split('/[^A-Za-z]+/', $nombre)));

        if (empty($palabras)) {
            throw new \RuntimeException('No se puede generar un código: el nombre debe contener letras.');
        }

        $primeraInicial = strtoupper($palabras[0][0]);
        $fuentes = count($palabras) > 1
            ? array_slice($palabras, 1)
            : [substr($palabras[0], 1)];
        $candidatos = [];

        foreach ($fuentes as $fuente) {
            $letras = str_split(strtoupper($fuente));
            if (empty($letras)) {
                continue;
            }

            // Primero usa la inicial: TP-LINK genera TL.
            $candidatos[] = $primeraInicial.$letras[0];

            // Si ya existe, intenta la última consonante: TP-LINK genera TK.
            $consonantes = array_values(array_filter($letras, fn (string $letra) => ! in_array($letra, ['A', 'E', 'I', 'O', 'U'], true)));
            if ($consonantes) {
                $candidatos[] = $primeraInicial.end($consonantes);
                foreach (array_reverse($consonantes) as $consonante) {
                    $candidatos[] = $primeraInicial.$consonante;
                }
            }

            foreach ($letras as $letra) {
                $candidatos[] = $primeraInicial.$letra;
            }
        }

        foreach (array_unique($candidatos) as $codigo) {
            if (preg_match('/^[A-Z]{2}$/', $codigo) && ! static::query()->where('codigo', $codigo)->exists()) {
                return $codigo;
            }
        }

        throw new \RuntimeException('No hay una combinación de dos letras disponible para este nombre.');
    }
}
