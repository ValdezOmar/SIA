<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\GrupoArticuloResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\GrupoArticuloResource;

use Filament\Resources\Pages\CreateRecord;

class CreateGrupoArticulo extends CreateRecord
{
    protected static string $resource = GrupoArticuloResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
