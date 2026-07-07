<?php

namespace App\Filament\Resources\Ventas\CotizacionResource\Pages;

use App\Filament\Resources\Ventas\CotizacionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCotizacion extends CreateRecord
{
    protected static string $resource = CotizacionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
