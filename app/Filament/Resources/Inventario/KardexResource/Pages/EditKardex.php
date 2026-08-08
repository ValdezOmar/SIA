<?php

namespace App\Filament\Resources\Inventario\KardexResource\Pages;

use App\Filament\Resources\Inventario\KardexResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKardex extends EditRecord
{
    protected static string $resource = KardexResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
