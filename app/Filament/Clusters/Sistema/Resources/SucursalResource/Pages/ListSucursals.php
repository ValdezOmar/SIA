<?php

namespace App\Filament\Clusters\Sistema\Resources\SucursalResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Sistema\Resources\SucursalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSucursals extends ListRecords
{
    protected static string $resource = SucursalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
