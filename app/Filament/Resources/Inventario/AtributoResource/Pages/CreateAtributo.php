<?php

namespace App\Filament\Resources\Inventario\AtributoResource\Pages;

use App\Filament\Resources\Inventario\AtributoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAtributo extends CreateRecord
{
    protected static string $resource = AtributoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
