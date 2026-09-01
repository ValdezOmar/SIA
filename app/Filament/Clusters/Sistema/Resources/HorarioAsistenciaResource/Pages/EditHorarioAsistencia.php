<?php

namespace App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\Pages;

use App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHorarioAsistencia extends EditRecord
{
    protected static string $resource = HorarioAsistenciaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
