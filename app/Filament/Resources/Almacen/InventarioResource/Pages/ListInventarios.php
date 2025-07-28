<?php

namespace App\Filament\Resources\Almacen\InventarioResource\Pages;

use App\Filament\Resources\Almacen\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Almacen\Articulo;
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
                    $articulos = Articulo::all();

                    foreach ($articulos as $articulo) {
                        Inventario::create([
                            'codigo' => $articulo->codigo,
                            'descripcion' => $articulo->descripcion,
                            'presentacion' => $articulo->presentacion,
                            'unidad' => $articulo->unidad,
                            'codigo_alterno' => $articulo->codigo_alterno,
                            'cod_almacen' => $articulo->cod_almacen,
                            'nombre_almacen' => $articulo->nombre_almacen,
                            'lote' => $articulo->lote,
                            'fecha_ven' => $articulo->fecha_ven,
                            'sn_qr' => $articulo->sn_qr,
                            'empresa' => $articulo->empresa,
                            'saldo_actual' => $articulo->saldo_actual,
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