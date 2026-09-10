<?php

namespace App\Filament\Resources\Contabilidad\ProyectoResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Contabilidad\ProyectoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProyecto extends EditRecord
{
    protected static string $resource = ProyectoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
