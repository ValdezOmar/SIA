<?php

namespace App\Filament\Resources\RRHH\AsistenciaResource\Pages;

use App\Filament\Resources\RRHH\AsistenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAsistencias extends ListRecords
{
    protected static string $resource = AsistenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
