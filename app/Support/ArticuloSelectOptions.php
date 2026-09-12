<?php

namespace App\Support;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArticuloSelectOptions
{
    public static function ventas(?string $search = null): array
    {
        $user = Auth::user();
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
        return self::formatArticulo($articulo, mostrarStock: true);
    }

    /**
     * Formatea la identificación visual del artículo sin disponibilidad.
     * Úselo cuando el contexto ya muestra el stock por separado.
     */
    public static function formatSinStock(Articulo $articulo): string
    {
        return self::formatArticulo($articulo, mostrarStock: false);
    }

    private static function formatArticulo(Articulo $articulo, bool $mostrarStock): string
    {
        $codigo = e($articulo->codigo ?: 'Sin código');
        $modelo = e($articulo->codigo_alterno ?: 'Sin modelo');
        $nombre = e($articulo->nombre_comercial ?: $articulo->descripcion ?: 'Sin nombre');
        $marca = e($articulo->fabricante?->nombre ?: 'Sin marca');
        $stock = (float) ($articulo->stock_disponible ?? 0);
        $fotoPredeterminada = asset('images/default-product.jpg');
        $foto = self::fotoUrl($articulo->foto_catalogo) ?? $fotoPredeterminada;
        $miniatura = '<img src="'.e($foto).'" alt="'.e($nombre).'"'
            .' style="width:4rem;height:4rem;flex:0 0 4rem;border-radius:.5rem;object-fit:cover;background:#f1f5f9"'
            .' onerror="this.onerror=null;this.src=\''.e($fotoPredeterminada).'\';">';

        return '<div style="display:flex;align-items:center;gap:.7rem;padding:.3rem 0;min-width:0">'.$miniatura
            .'<div style="min-width:0;flex:1;line-height:1.3">'
            .'<div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#0f172a;font-size:.82rem;font-weight:800">'.$codigo.'</div>'
            .'<div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#475569;font-size:.72rem">Modelo: '.$modelo.'</div>'
            .'<div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#334155;font-size:.74rem;font-weight:650">'.$nombre.'</div>'
            .($mostrarStock ? self::stockHtml($articulo->inventariable, $stock) : '')
            .'<div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;font-size:.7rem">Marca: '.$marca.'</div>'
            .'</div></div>';
    }

    private static function stockHtml(bool $inventariable, float $stock): string
    {
        if (! $inventariable) {
            return '<div class="text-xs font-medium text-gray-500 dark:text-gray-400">Servicio · sin stock</div>';
        }

        if ($stock <= 1) {
            return '<div class="text-danger-600" style="margin-top:.08rem;color:#dc2626;font-size:.72rem;font-weight:800">Stock: '.($stock <= 0 ? 'Sin stock' : '1 unidad').'</div>';
        }

        $color = $stock > 5 ? '#15803d' : '#d97706';
        $class = $stock > 5 ? 'text-success-600' : 'text-warning-600';

        return '<div class="'.$class.'" style="margin-top:.08rem;color:'.$color.';font-size:.72rem;font-weight:800">Stock: '.e(number_format($stock, 2, ',', '.')).'</div>';
    }

    private static function fotoUrl(?string $foto): ?string
    {
        if (blank($foto)) {
            return null;
        }

        $foto = trim($foto);

        if (str_starts_with($foto, 'http://') || str_starts_with($foto, 'https://')) {
            return $foto;
        }

        if (str_starts_with($foto, '/')) {
            return asset(ltrim($foto, '/'));
        }

        if (is_file(public_path($foto))) {
            return asset($foto);
        }

        return Storage::disk('public')->url($foto);
    }

    private static function almacenVentaId(): ?int
    {
        $user = Auth::user();

        return Almacen::query()
            ->where('activo', true)
            ->when($user?->empresa_id, fn ($query) => $query->where('empresa_id', $user->empresa_id))
            ->when($user?->sucursal_id, fn ($query) => $query
                ->where(fn ($almacenes) => $almacenes->where('sucursal_id', $user->sucursal_id)->orWhereNull('sucursal_id'))
                ->orderByRaw('sucursal_id IS NULL'))
            ->value('id');
    }
}
