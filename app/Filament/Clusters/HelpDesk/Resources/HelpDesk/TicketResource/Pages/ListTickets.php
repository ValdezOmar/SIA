<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\TicketResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
