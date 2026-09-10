<?php

namespace App\Filament\Resources\RRHH\AsistenciaResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\RRHH\AsistenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAsistencia extends EditRecord
{
    protected static string $resource = AsistenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
