<?php

namespace App\Filament\Resources\Inventario\GrupoArticuloResource\Pages;

use App\Filament\Resources\Inventario\GrupoArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGrupoArticulo extends EditRecord
{
    protected static string $resource = GrupoArticuloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
