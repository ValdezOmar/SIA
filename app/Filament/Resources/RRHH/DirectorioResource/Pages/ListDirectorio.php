<?php

namespace App\Filament\Resources\RRHH\DirectorioResource\Pages;

use App\Filament\Resources\RRHH\DirectorioResource;
use Filament\Resources\Pages\ListRecords;

class ListDirectorio extends ListRecords
{
    protected static string $resource = DirectorioResource::class;

    public function getSubheading(): ?string
    {
        return 'Consulte rápidamente los datos de contacto y la asignación laboral vigente del personal.';
    }

    protected function getHeaderActions(): array
    {
        return []; // No incluir acciones de creación
    }
}
