<?php

namespace App\Filament\Resources\RRHH\AsistenciaResource\Pages;

use App\Filament\Resources\RRHH\AsistenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Blade;

class CreateAsistencia extends CreateRecord
{
    // protected static string $resource = AsistenciaResource::class;

    // protected function getFormSchema(): array
    // {
    //     return [
    //         \Filament\Forms\Components\View::make('livewire.gps-location')
    //             ->label('Verificación de Ubicación'),
                
    //         // ... otros campos del formulario
    //     ];
    // }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // Forzar registro remoto
    //     $data['registro_remoto'] = true;
        
    //     return $data;
    // }

    // protected function getCreatedNotificationTitle(): ?string
    // {
    //     return 'Asistencia registrada correctamente';
    // }

    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }
}