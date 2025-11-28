<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

    // Redirigir al index después de crear
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}