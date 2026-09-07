<?php

namespace App\Support;

use App\Models\Ventas\Cliente;
use Illuminate\Database\Eloquent\Builder;

class ClienteSelectOptions
{
    public static function ventas(?int $empresaId, ?string $search = null): array
    {
        return self::query($empresaId)
            ->where('activo', true)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $search = trim($search);
                $numero = Cliente::normalizarCelular($search);
                $query->where(function (Builder $query) use ($search, $numero): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%")
                        ->orWhere('celular', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%");

                    if ($numero && preg_match('/^[\d\s+().-]+$/', $search)) {
                        foreach (['celular', 'telefono'] as $campo) {
                            $expresion = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$campo}, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '.', '')";
                            $query->orWhereRaw("{$expresion} LIKE ?", ["%{$numero}%"]);
                        }
                    }
                });
            })
            ->orderBy('nombre')
            ->limit(50)
            ->get(['id', 'nombre', 'celular', 'telefono'])
            ->mapWithKeys(fn (Cliente $cliente): array => [$cliente->id => self::label($cliente)])
            ->all();
    }

    public static function seleccionado(mixed $id, ?int $empresaId): ?string
    {
        $cliente = filled($id) ? self::query($empresaId)->find($id) : null;

        return $cliente ? self::label($cliente) : null;
    }

    private static function query(?int $empresaId): Builder
    {
        return Cliente::query()->when($empresaId, fn (Builder $query) => $query->where('empresa_id', $empresaId));
    }

    private static function label(Cliente $cliente): string
    {
        $contacto = filled($cliente->celular) ? $cliente->celular : $cliente->telefono;

        return $cliente->nombre.(filled($contacto) ? ' · '.$contacto : '');
    }
}
