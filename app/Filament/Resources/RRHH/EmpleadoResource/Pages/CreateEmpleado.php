<?php

namespace App\Filament\Resources\RRHH\EmpleadoResource\Pages;

use App\Filament\Resources\RRHH\EmpleadoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmpleado extends CreateRecord
{
    protected static string $resource = EmpleadoResource::class;

    protected static ?string $title = 'Registrar empleado';

    public ?array $ubicacion_gps = null;

    public function getSubheading(): ?string
    {
        return 'Complete los datos personales y después agregue su asignación laboral.';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $ubicacion = $this->ubicacion_gps ?? ($data['ubicacion_gps'] ?? null);

        if (is_array($ubicacion) && isset($ubicacion['lat'], $ubicacion['lng'])) {
            $latitud = round((float) $ubicacion['lat'], 6);
            $longitud = round((float) $ubicacion['lng'], 6);

            $data['ubicacion_gps'] = ($latitud === -16.5 && $longitud === -68.15)
                ? null
                : ['lat' => $latitud, 'lng' => $longitud];
        } else {
            $data['ubicacion_gps'] = null;
        }

        return static::getModel()::create($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
