<?php

namespace App\Filament\Resources\RRHH\EmpleadoResource\Pages;

use App\Filament\Resources\RRHH\EmpleadoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmpleados extends ListRecords
{
    protected static string $resource = EmpleadoResource::class;

    protected static ?string $title = 'Equipo humano';

    public function getSubheading(): ?string
    {
        return 'Consulte empleados, asignaciones laborales, contratos y datos de contacto desde una sola vista.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Registrar empleado')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
