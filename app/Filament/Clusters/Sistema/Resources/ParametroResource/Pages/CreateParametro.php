<?php

namespace App\Filament\Clusters\Sistema\Resources\ParametroResource\Pages;

use App\Filament\Clusters\Sistema\Resources\ParametroResource;
use Filament\Resources\Pages\CreateRecord;

class CreateParametro extends CreateRecord
{
    protected static string $resource = ParametroResource::class;

    // Redirigir al index
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
