<?php

namespace App\Filament\Resources\RRHH\EmpleadoResource\Pages;

use App\Filament\Resources\RRHH\EmpleadoResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditEmpleado extends EditRecord
{
    protected static string $resource = EmpleadoResource::class;

    public ?array $ubicacion_gps = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $ubicacion = $this->getRecord()->ubicacion_gps;

        $this->ubicacion_gps = is_array($ubicacion) ? $ubicacion : null;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Ficha de '.$this->getRecord()->full_name;
    }

    public function getSubheading(): ?string
    {
        return 'Actualice sus datos personales y administre el historial laboral desde la pestaña inferior.';
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        if (! is_array($this->ubicacion_gps)) {
            // No sobrescribir datos históricos inválidos al editar otros campos.
            // Una nueva selección en el mapa llegará como un arreglo válido.
            unset($data['ubicacion_gps']);

            return $data;
        }

        $latitud = round((float) ($this->ubicacion_gps['lat'] ?? 0), 6);
        $longitud = round((float) ($this->ubicacion_gps['lng'] ?? 0), 6);

        $data['ubicacion_gps'] = ($latitud === -16.5 && $longitud === -68.15)
            ? null
            : ['lat' => $latitud, 'lng' => $longitud];

        return $data;
    }
}
