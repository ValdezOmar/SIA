<?php

namespace App\Filament\Clusters\Sistema\Resources\ParametroResource\Pages;

use App\Filament\Clusters\Sistema\Resources\ParametroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParametro extends EditRecord
{
    protected static string $resource = ParametroResource::class;

    //Redirigir al index
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}