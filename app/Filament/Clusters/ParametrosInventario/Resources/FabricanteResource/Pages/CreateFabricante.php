<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\FabricanteResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\FabricanteResource;

use Filament\Resources\Pages\CreateRecord;

class CreateFabricante extends CreateRecord
{
    protected static string $resource = FabricanteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
