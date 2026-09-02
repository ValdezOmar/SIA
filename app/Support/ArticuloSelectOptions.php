<?php

namespace App\Support;

use App\Models\Inventario\Articulo;
use Illuminate\Support\Facades\Storage;

class ArticuloSelectOptions
{
    public static function ventas(?string $search = null): array
    {
        $user = auth()->user();

        return Articulo::query()
            ->with('fabricante:id,nombre,codigo')
            ->where('activo', true)
            ->where('vendible', true)
            ->when($user?->empresa_id, fn ($query) => $query->where('empresa_id', $user->empresa_id))
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('codigo', 'like', "%{$search}%")
                        ->orWhere('codigo_alterno', 'like', "%{$search}%")
                        ->orWhere('nombre_comercial', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhereHas('fabricante', fn ($fabricante) => $fabricante
                            ->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%"));
                });
            })
            ->orderBy('codigo')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Articulo $articulo) => [$articulo->getKey() => self::format($articulo)])
            ->all();
    }

    public static function label(mixed $value): ?string
    {
        $articulo = Articulo::query()->with('fabricante:id,nombre,codigo')->find($value);

        return $articulo ? self::format($articulo) : null;
    }

    public static function format(Articulo $articulo): string
    {
        $codigo = e($articulo->codigo ?: 'Sin código');
        $modelo = e($articulo->codigo_alterno ?: 'Sin modelo');
        $nombre = e($articulo->nombre_comercial ?: $articulo->descripcion ?: 'Sin nombre');
        $marca = e($articulo->fabricante?->nombre ?: 'Sin marca');

        $miniatura = filled($articulo->foto_catalogo)
            ? '<img src="'.e(Storage::disk('public')->url($articulo->foto_catalogo)).'" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-white/10">'
            : '<span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-2xl dark:bg-gray-800">&#128230;</span>';

        return '<div class="flex items-center gap-3 py-2">'.$miniatura
            .'<div class="min-w-0 flex-1 leading-tight">'
            .'<div class="truncate text-sm font-semibold text-gray-950 dark:text-white">'.$codigo.'</div>'
            .'<div class="truncate text-xs text-gray-600 dark:text-gray-300">Modelo: '.$modelo.'</div>'
            .'<div class="truncate text-xs text-gray-600 dark:text-gray-300">'.$nombre.'</div>'
            .'<div class="truncate text-xs text-gray-500 dark:text-gray-400">Marca: '.$marca.'</div>'
            .'</div></div>';
    }
}
