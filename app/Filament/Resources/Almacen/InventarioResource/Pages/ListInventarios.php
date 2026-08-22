<?php

namespace App\Filament\Resources\Almacen\InventarioResource\Pages;

use App\Filament\Resources\Almacen\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Inventario\Articulo;
use Filament\Forms\Components\DatePicker;
use App\Models\Almacen\Inventario;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Filament\Resources\Almacen\InventarioResource\Widgets\InventarioStats;

class ListInventarios extends ListRecords
{
    protected static string $resource = InventarioResource::class;
    use ExposesTableToWidgets;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Programar Inventario')
                ->label('Programar Inventario')
                ->color('success')
                ->form([
                    DatePicker::make('fecha_conteo_inventario')
                        ->label('Fecha del conteo')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $fecha = $data['fecha_conteo_inventario'];

                    // 1. Desactivar todos los inventarios anteriores
                    Inventario::where('fecha_conteo_inventario', '!=', $fecha)
                        ->update(['activo' => false]);

                    // 2. Crear nuevo inventario para cada artículo
                    $articulos = Articulo::with(['unidadMedida', 'existencias.almacen'])->get();

                    foreach ($articulos as $articulo) {
                        $existencia = $articulo->existencias->first();

                        Inventario::create([
                            'codigo' => $articulo->codigo,
                            'descripcion' => $articulo->descripcion,
                            'presentacion' => $articulo->nombre_comercial,
                            'unidad' => $articulo->unidadMedida?->abreviatura,
                            'codigo_alterno' => $articulo->codigo_alterno,
                            'cod_almacen' => $existencia?->almacen?->codigo,
                            'nombre_almacen' => $existencia?->almacen?->nombre,
                            'empresa' => $articulo->empresa?->nombre_comercial,
                            'saldo_actual' => $existencia?->cantidad_disponible ?? 0,
                            'fecha_conteo_inventario' => $fecha,
                            'activo' => true,
                        ]);
                    }

                    Notification::make()
                        ->title('Inventario programado correctamente')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn() => Auth::user()?->can('programar_inventario_almacen::inventario')),
        ];
    }
    //Header widget que muestra lostats del progeso de inventario realizado
    protected function getHeaderWidgets(): array
    {
        return [
            InventarioStats::class,
        ];
    }
    protected function getTableFiltersFormWidth(): string
    {
        return '4xl';
    }
    //Actualiza la tabla en tiempo real
    public function updatedTableFilters(): void
    {
        $this->dispatch('updateFilters');
    }
}