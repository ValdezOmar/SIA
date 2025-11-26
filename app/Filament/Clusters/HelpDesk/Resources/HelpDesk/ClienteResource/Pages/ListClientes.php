<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
