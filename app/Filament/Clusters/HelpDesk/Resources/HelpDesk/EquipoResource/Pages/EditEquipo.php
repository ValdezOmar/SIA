<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipo extends EditRecord
{
    protected static string $resource = EquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
