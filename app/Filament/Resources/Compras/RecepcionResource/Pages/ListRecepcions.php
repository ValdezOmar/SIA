<?php

namespace App\Filament\Resources\Compras\RecepcionResource\Pages;

use App\Filament\Resources\Compras\RecepcionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecepcions extends ListRecords
{
    protected static string $resource = RecepcionResource::class;

    public function getSubheading(): ?string
    {
        return 'Paso 3: registre lo recibido, indique cantidades aceptadas y el almacén; después use “Procesar ingreso”.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
