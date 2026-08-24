<?php

namespace App\Filament\Resources\Ventas\CotizacionResource\Pages;

use App\Filament\Resources\Ventas\CotizacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCotizaciones extends ListRecords
{
    protected static string $resource = CotizacionResource::class;

    public function getSubheading(): ?string
    {
        return 'Una cotización es una propuesta de precios. Úsela antes de que el cliente confirme; no genera una venta ni mueve inventario.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
