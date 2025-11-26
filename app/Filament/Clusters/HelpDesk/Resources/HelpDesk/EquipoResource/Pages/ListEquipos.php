<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipos extends ListRecords
{
    protected static string $resource = EquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
