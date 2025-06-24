<?php

namespace App\Filament\Resources\Almacen\ArticuloResource\Pages;

use App\Filament\Resources\Almacen\ArticuloResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Almacen\Articulo;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Filament\Resources\Almacen\ArticuloResource\Widgets\ArticuloStats;

class ListArticulos extends ListRecords
{
    protected static string $resource = ArticuloResource::class;
    use ExposesTableToWidgets;

    public function getTabs(): array
    {
        // Códigos fijos para Comercial
        $codigosComercial = ['101', '102', '107', '201', '202', '207', '301', '302', '307', '401', '402', '407', '501', '502', '507'];

        // Códigos fijos para Almacén (101-513)
        $codigosAlmacen = range(101, 513);

        // Límite máximo para la pestaña Todos (101-599)
        $limiteTodos = range(101, 599);

        return [
            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-list-bullet')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->whereIn('cod_almacen', $limiteTodos))
                ->badge(Articulo::whereIn('cod_almacen', $limiteTodos)->count()),

            'comercial' => Tab::make('Comercial')
                ->icon('heroicon-o-shopping-bag')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->whereIn('cod_almacen', $codigosComercial)
                    ->where('saldo_actual', '>', 0))
                ->badge(Articulo::whereIn('cod_almacen', $codigosComercial)
                    ->where('saldo_actual', '>', 0)
                    ->count()),

            'almacen' => Tab::make('Almacén')
                ->icon('heroicon-o-building-storefront')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->whereIn('cod_almacen', $codigosAlmacen)
                    ->where('saldo_actual', '>', 0))
                ->badge(Articulo::whereIn('cod_almacen', $codigosAlmacen)
                    ->where('saldo_actual', '>', 0)
                    ->count()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ArticuloStats::class,
        ];
    }
}