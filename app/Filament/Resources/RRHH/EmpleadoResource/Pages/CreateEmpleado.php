<?php

namespace App\Filament\Resources\RRHH\EmpleadoResource\Pages;

use App\Filament\Resources\RRHH\EmpleadoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmpleado extends CreateRecord
{
    protected static string $resource = EmpleadoResource::class;

    public ?array $ubicacion_gps;

    // Redirigir al listado principal después de guardar
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}