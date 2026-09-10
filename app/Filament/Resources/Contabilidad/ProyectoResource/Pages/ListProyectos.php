<?php

namespace App\Filament\Resources\Contabilidad\ProyectoResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Contabilidad\ProyectoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProyectos extends ListRecords
{
    protected static string $resource = ProyectoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
