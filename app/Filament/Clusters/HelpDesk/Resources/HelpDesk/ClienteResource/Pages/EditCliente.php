<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\DeleteAction::make(),
        ];
    }
    
    // Redirigir al index después de crear
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}