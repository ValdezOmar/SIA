<?php

namespace App\Filament\Resources\RRHH\EmpleadoResource\Pages;

use App\Filament\Resources\RRHH\EmpleadoResource;
use Filament\Resources\Pages\EditRecord;

class EditEmpleado extends EditRecord
{
    protected static string $resource = EmpleadoResource::class;

    public ?array $ubicacion_gps;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
    //Funcion para guardar el array de gps
    public function mutateFormDataBeforeSave(array $data): array
    { //dump($this->data);
        // Ver lo que contiene la propiedad para depuración
        //dump($this->ubicacion_gps);
        if (is_array($this->ubicacion_gps)) {
        $lat = round(floatval($this->ubicacion_gps['lat'] ?? 0), 6);
        $lng = round(floatval($this->ubicacion_gps['lng'] ?? 0), 6);

        // Verifica si la ubicación coincide con la coordenada específica
        if ($lat === -16.504759 && $lng === -68.119124) {
            $data['ubicacion_gps'] = null;
        } else {
            $data['ubicacion_gps'] = [
                'lat' => $lat,
                'lng' => $lng,
            ];
        }
    } else {
        $data['ubicacion_gps'] = null;
    }

        return $data;
    }
}
