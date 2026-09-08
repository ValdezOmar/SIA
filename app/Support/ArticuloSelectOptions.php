<?php

namespace App\Support;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use Illuminate\Support\Facades\Storage;

class ArticuloSelectOptions
{
    public static function ventas(?string $search = null): array
    {
        $user = auth()->user();
        $almacenId = self::almacenVentaId();

        return Articulo::query()
            ->with('fabricante:id,nombre,codigo')
            ->withSum([
                'existencias as stock_disponible' => fn ($query) => $almacenId
                    ? $query->where('almacen_id', $almacenId)
                    : $query->whereRaw('1 = 0'),
            ], 'cantidad_disponible')
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
        $almacenId = self::almacenVentaId();
        $articulo = Articulo::query()
            ->with('fabricante:id,nombre,codigo')
            ->withSum([
                'existencias as stock_disponible' => fn ($query) => $almacenId
                    ? $query->where('almacen_id', $almacenId)
                    : $query->whereRaw('1 = 0'),
            ], 'cantidad_disponible')
            ->find($value);

        return $articulo ? self::format($articulo) : null;
    }

    public static function format(Articulo $articulo): string
    {
        $codigo = e($articulo->codigo ?: 'Sin código');
        $modelo = e($articulo->codigo_alterno ?: 'Sin modelo');
        $nombre = e($articulo->nombre_comercial ?: $articulo->descripcion ?: 'Sin nombre');
        $marca = e($articulo->fabricante?->nombre ?: 'Sin marca');
        $stock = (float) ($articulo->stock_disponible ?? 0);
        $miniatura = filled($articulo->foto_catalogo)
            ? '<img src="'.e(Storage::disk('public')->url($articulo->foto_catalogo)).'" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-white/10">'
            : '<span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-2xl dark:bg-gray-800">&#128230;</span>';

        return '<div class="flex items-center gap-3 py-2">'.$miniatura
            .'<div class="min-w-0 flex-1 leading-tight">'
            .'<div class="truncate text-sm font-semibold text-gray-950 dark:text-white">'.$codigo.'</div>'
            .'<div class="truncate text-xs text-gray-600 dark:text-gray-300">Modelo: '.$modelo.'</div>'
            .'<div class="truncate text-xs text-gray-600 dark:text-gray-300">'.$nombre.'</div>'
            .self::stockHtml($articulo->inventariable, $stock)
            .'<div class="truncate text-xs text-gray-500 dark:text-gray-400">Marca: '.$marca.'</div>'
            .'</div></div>';
    }

    private static function stockHtml(bool $inventariable, float $stock): string
    {
        if (! $inventariable) {
            return '<div class="text-xs font-medium text-gray-500 dark:text-gray-400">Servicio · sin stock</div>';
        }

        if ($stock <= 0) {
            return '<div class="text-xs font-semibold text-danger-600 dark:text-danger-400">Stock: Sin stock</div>';
        }

        $color = $stock > 5
            ? 'text-success-600 dark:text-success-400'
            : ($stock > 1 ? 'text-warning-600 dark:text-warning-400' : 'text-danger-600 dark:text-danger-400');

        return '<div class="text-xs font-semibold '.$color.'">Stock: '.e(number_format($stock, 2, ',', '.')).'</div>';
    }

    private static function almacenVentaId(): ?int
    {
        $user = auth()->user();

        return Almacen::query()
            ->where('activo', true)
            ->when($user?->empresa_id, fn ($query) => $query->where('empresa_id', $user->empresa_id))
            ->when($user?->sucursal_id, fn ($query) => $query
                ->where(fn ($almacenes) => $almacenes->where('sucursal_id', $user->sucursal_id)->orWhereNull('sucursal_id'))
                ->orderByRaw('sucursal_id IS NULL'))
            ->value('id');
    }
}
