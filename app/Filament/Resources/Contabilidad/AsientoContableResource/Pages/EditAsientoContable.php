<?php

namespace App\Filament\Resources\Contabilidad\AsientoContableResource\Pages;

use App\Filament\Resources\Contabilidad\AsientoContableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAsientoContable extends EditRecord
{
    protected static string $resource = AsientoContableResource::class;

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
