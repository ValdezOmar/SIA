<?php

namespace App\Filament\Resources\Almacen\ArticuloResource\Pages;

use App\Filament\Resources\Almacen\ArticuloResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Almacen\Articulo;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\Almacen\ArticuloResource\Widgets\ArticuloStats;

class ListArticulos extends ListRecords
{
    protected static string $resource = ArticuloResource::class;
    use ExposesTableToWidgets;

    //Tabs de la vista en la la lista
    public function getTabs(): array
    {
        $tabs = [];
        $user = Auth::user();

        $codigosComercial = ['101', '102', '107', '201', '202', '207', '301', '302', '307', '401', '402', '407', '501', '502', '507'];
        $codigosAlmacen = range(101, 513);
        $limiteTodos = range(101, 599);       

        // "Comercial"
        if ($user->can('tab_comercial_almacen::articulo')) {
            $tabs['comercial'] = Tab::make('Comercial')
                ->icon('heroicon-o-shopping-bag')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->whereIn('cod_almacen', $codigosComercial)
                    ->where('saldo_actual', '>', 0))
                ->badge(Articulo::whereIn('cod_almacen', $codigosComercial)
                    ->where('saldo_actual', '>', 0)
                    ->count());
        }

        // "Almacén"
        if ($user->can('tab_almacen_almacen::articulo')) {
            $tabs['almacen'] = Tab::make('Almacén')
                ->icon('heroicon-o-building-storefront')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->whereIn('cod_almacen', $codigosAlmacen)
                    ->where('saldo_actual', '>', 0))
                ->badge(Articulo::whereIn('cod_almacen', $codigosAlmacen)
                    ->where('saldo_actual', '>', 0)
                    ->count());
        }

        // "Todos" — permiso personalizado
        if ($user->can('tab_todos_almacen::articulo')) {
            $tabs['todos'] = Tab::make('Todo')
                ->icon('heroicon-o-list-bullet')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('cod_almacen', $limiteTodos))
                ->badge(Articulo::whereIn('cod_almacen', $limiteTodos)->count());
        }

        return $tabs;
    }

    //Registra el widget del header como ruta 
    protected function getHeaderWidgets(): array
    {
        return [
            ArticuloStats::class,
        ];
    }
}