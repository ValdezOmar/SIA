<?php

namespace App\Filament\Resources\RRHH\EmpleadoResource\Pages;

use App\Filament\Resources\RRHH\EmpleadoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmpleado extends CreateRecord
{
    protected static string $resource = EmpleadoResource::class;

    public ?array $ubicacion_gps;
    
    protected function handleRecordCreation(array $data): Model
    {
        // 1. Sincronizamos lo que venga desde el form hacia la propiedad
        $this->ubicacion_gps = $this->ubicacion_gps ?? ($data['ubicacion_gps'] ?? null);        

        // 2. Sobrescribimos el valor del formulario con la propiedad
        $data['ubicacion_gps'] = $this->ubicacion_gps;

        // 3. Guardamos el registro manualmente
        return static::getModel()::create($data);
    }
}