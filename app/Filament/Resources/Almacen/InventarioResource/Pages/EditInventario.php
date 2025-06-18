<?php

namespace App\Filament\Resources\Almacen\InventarioResource\Pages;

use App\Filament\Resources\Almacen\InventarioResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\URL;

class EditInventario extends EditRecord
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
    // Mantener los filtros al volver al index
    public function mount($record): void
    {
        parent::mount($record);

        // Solo guardar si se vino desde el index con filtros
        if (str_contains(URL::previous(), InventarioResource::getUrl('index'))) {
            session()->put('inventario_return_url', URL::previous());
        }
    }
    protected function getRedirectUrl(): string
    {
        return session()->pull('inventario_return_url', InventarioResource::getUrl('index'));
    }
}