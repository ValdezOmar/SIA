<?php

namespace App\Filament\Resources\RRHH\DirectorioResource\Pages;

use App\Filament\Resources\RRHH\DirectorioResource;
use Filament\Resources\Pages\ListRecords;

class ListDirectorio extends ListRecords
{
    protected static string $resource = DirectorioResource::class;

    protected function getHeaderActions(): array
    {
        return []; // No incluir acciones de creación
    }
}