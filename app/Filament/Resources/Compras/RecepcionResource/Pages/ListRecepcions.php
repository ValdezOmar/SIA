<?php

namespace App\Filament\Resources\Compras\RecepcionResource\Pages;

use App\Filament\Resources\Compras\RecepcionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecepcions extends ListRecords
{
    protected static string $resource = RecepcionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
