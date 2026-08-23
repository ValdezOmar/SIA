<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUbicacions extends ListRecords
{
    protected static string $resource = UbicacionResource::class;

    public function getSubheading(): ?string
    {
        return 'Identifique pasillos, estantes y niveles para encontrar el stock sin demora.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nueva ubicación'),
        ];
    }
}
