<?php

namespace App\Filament\Resources\Compras\RecepcionResource\Pages;

use App\Filament\Resources\Compras\RecepcionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecepcion extends EditRecord
{
    protected static string $resource = RecepcionResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
