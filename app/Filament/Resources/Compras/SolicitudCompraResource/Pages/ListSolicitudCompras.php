<?php

namespace App\Filament\Resources\Compras\SolicitudCompraResource\Pages;

use App\Filament\Resources\Compras\SolicitudCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicitudCompras extends ListRecords
{
    protected static string $resource = SolicitudCompraResource::class;

    public function getSubheading(): ?string
    {
        return 'Paso 1: registre qué necesita, para cuándo y con qué prioridad. Luego envíela para aprobación.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
