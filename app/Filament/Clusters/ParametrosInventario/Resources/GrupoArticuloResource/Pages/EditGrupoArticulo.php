<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\GrupoArticuloResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\GrupoArticuloResource;
use Filament\Resources\Pages\EditRecord;

class EditGrupoArticulo extends EditRecord
{
    protected static string $resource = GrupoArticuloResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\DeleteAction::make(),
        ];
    }
}
