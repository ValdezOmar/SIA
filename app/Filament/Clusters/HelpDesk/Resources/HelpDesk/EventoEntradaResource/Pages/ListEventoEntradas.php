<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEventoEntradas extends ListRecords
{
    protected static string $resource = EventoEntradaResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\CreateAction::make(),
        ];
    }
}