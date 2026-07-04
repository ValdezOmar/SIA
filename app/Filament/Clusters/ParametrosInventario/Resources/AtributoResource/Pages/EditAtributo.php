<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\AtributoResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\AtributoResource;
use Filament\Resources\Pages\EditRecord;

class EditAtributo extends EditRecord
{
    protected static string $resource = AtributoResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\DeleteAction::make(),
        ];
    }
}
