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

class ListInventarios extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
            Action::make('Programar Inventario')
            ->label('Programar Inventario')
            ->color('success')
            ->form([
                DatePicker::make('fecha_conteo_inventario')
                    ->label('Fecha del conteo')
                    ->required(),
            ])
            ->action(function (array $data): void {
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

                        'fecha_conteo_inventario' => $data['fecha_conteo_inventario'],
                        'activo' => true,
                        //'usuario' => Auth::user()?->name ?? 'sistema',
                    ]);
                }

                Notification::make()
                    ->title('Inventario programado correctamente')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->visible(fn () => Auth::user()?->can('programar_inventario_almacen::inventario')),
        ];
    }
}