<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoSalidaResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoSalidaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEventoSalida extends EditRecord
{
    protected static string $resource = EventoSalidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
