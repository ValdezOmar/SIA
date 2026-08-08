<?php

namespace App\Filament\Resources\Compras\SolicitudCompraResource\Pages;

use App\Filament\Resources\Compras\SolicitudCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicitudCompras extends ListRecords
{
    protected static string $resource = SolicitudCompraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
