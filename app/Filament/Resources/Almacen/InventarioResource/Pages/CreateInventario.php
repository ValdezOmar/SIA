<?php

namespace App\Filament\Resources\Almacen\InventarioResource\Pages;

use App\Filament\Resources\Almacen\InventarioResource;
use App\Services\Inventario\InventarioFisicoService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInventario extends CreateRecord
{
    protected static string $resource = InventarioResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(InventarioFisicoService::class)->programar($data);
    }

    protected function getRedirectUrl(): string
    {
        return InventarioResource::getUrl('view', ['record' => $this->record]);
    }
}
