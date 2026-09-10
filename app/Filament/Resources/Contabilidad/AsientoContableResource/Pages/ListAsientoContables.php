<?php

namespace App\Filament\Resources\Contabilidad\AsientoContableResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Contabilidad\AsientoContableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAsientoContables extends ListRecords
{
    protected static string $resource = AsientoContableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
