<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoPendienteResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoPendienteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEventoPendiente extends EditRecord
{
    protected static string $resource = EventoPendienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\DeleteAction::make(),
        ];
    }
}