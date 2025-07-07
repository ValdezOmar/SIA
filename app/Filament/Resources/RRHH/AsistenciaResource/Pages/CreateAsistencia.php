<?php

namespace App\Filament\Resources\RRHH\AsistenciaResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;


class CreateAsistencia extends CreateRecord
{
    protected function getFormActions(): array
    {
        return []; // oculta todos los botones del formulario
    }
}