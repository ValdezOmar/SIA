<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\UnidadMedidaResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\UnidadMedidaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnidadMedidas extends ListRecords
{
    protected static string $resource = UnidadMedidaResource::class;

    public function getSubheading(): ?string
    {
        return 'Defina las unidades que usarán los artículos, por ejemplo unidad, kilogramo o litro.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nueva unidad de medida'),
        ];
    }
}
