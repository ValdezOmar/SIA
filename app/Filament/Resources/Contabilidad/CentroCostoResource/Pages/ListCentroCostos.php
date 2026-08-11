<?php

namespace App\Filament\Resources\Contabilidad\CentroCostoResource\Pages;

use App\Filament\Resources\Contabilidad\CentroCostoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCentroCostos extends ListRecords
{
    protected static string $resource = CentroCostoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
