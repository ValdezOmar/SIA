<?php

namespace App\Filament\Resources\Ventas\ClienteResource\Pages;

use App\Filament\Resources\Ventas\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }    
}
