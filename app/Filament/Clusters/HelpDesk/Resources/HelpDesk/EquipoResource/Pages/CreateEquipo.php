<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;


class CreateEquipo extends CreateRecord
{
    protected static string $resource = EquipoResource::class;
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
    
    // Redirigir al index después de crear
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}