<?php

namespace App\Filament\Resources\Comercial\CatalogoResource\Pages;

use App\Filament\Resources\Comercial\CatalogoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatalogo extends EditRecord
{
    protected static string $resource = CatalogoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}