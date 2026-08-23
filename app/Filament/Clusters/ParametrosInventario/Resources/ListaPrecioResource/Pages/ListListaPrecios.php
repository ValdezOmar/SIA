<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListListaPrecios extends ListRecords
{
    protected static string $resource = ListaPrecioResource::class;

    public function getSubheading(): ?string
    {
        return 'Cree una lista antes de asignar precios a los artículos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nueva lista de precios'),
        ];
    }
}
