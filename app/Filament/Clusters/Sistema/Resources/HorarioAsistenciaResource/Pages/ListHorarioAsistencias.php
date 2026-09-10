<?php

namespace App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHorarioAsistencias extends ListRecords
{
    protected static string $resource = HorarioAsistenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
