<?php

namespace App\Filament\Resources\Inventario\GrupoArticuloResource\Pages;

use App\Filament\Resources\Inventario\GrupoArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGrupoArticulo extends CreateRecord
{
    protected static string $resource = GrupoArticuloResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
