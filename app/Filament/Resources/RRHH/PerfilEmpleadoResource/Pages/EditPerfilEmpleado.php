<?php

namespace App\Filament\Resources\RRHH\PerfilEmpleadoResource\Pages;

use App\Filament\Resources\RRHH\PerfilEmpleadoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPerfilEmpleado extends EditRecord
{
    protected static string $resource = PerfilEmpleadoResource::class;

    protected static ?string $title = 'Editar mi perfil';

    public ?array $ubicacion_gps = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->ubicacion_gps = $this->getRecord()->ubicacion_gps;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $ubicacion = $this->ubicacion_gps ?? ($data['ubicacion_gps'] ?? null);

        if (is_string($ubicacion)) {
            $ubicacion = json_decode($ubicacion, true);
        }

        if (! is_array($ubicacion) || ! isset($ubicacion['lat'], $ubicacion['lng'])) {
            $data['ubicacion_gps'] = null;

            return $data;
        }

        $latitud = round((float) $ubicacion['lat'], 6);
        $longitud = round((float) $ubicacion['lng'], 6);

        if ($latitud === -16.5 && $longitud === -68.15) {
            $data['ubicacion_gps'] = null;

            return $data;
        }

        $data['ubicacion_gps'] = [
            'lat' => $latitud,
            'lng' => $longitud,
        ];

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Ver perfil'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perfil actualizado correctamente';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }
}
