<?php

namespace App\Filament\Resources\Configuracion\UserResource\Pages;

use App\Filament\Resources\Configuracion\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
