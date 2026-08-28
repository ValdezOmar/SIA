<?php

namespace App\Filament\Resources\RRHH\PerfilEmpleadoResource\Pages;

use App\Filament\Resources\RRHH\PerfilEmpleadoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPerfilEmpleado extends ViewRecord
{
    protected static string $resource = PerfilEmpleadoResource::class;

    protected static ?string $title = 'Mi Perfil';

    public ?array $ubicacion_gps = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->ubicacion_gps = $this->getRecord()->ubicacion_gps;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar mis datos')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}
