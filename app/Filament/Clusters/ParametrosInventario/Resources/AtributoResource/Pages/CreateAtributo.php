<?php
namespace App\Filament\Clusters\ParametrosInventario\Resources\AtributoResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\AtributoResource;

use Filament\Resources\Pages\CreateRecord;

class CreateAtributo extends CreateRecord
{
    protected static string $resource = AtributoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
