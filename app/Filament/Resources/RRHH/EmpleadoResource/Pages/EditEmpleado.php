<?php

namespace App\Filament\Resources\RRHH\EmpleadoResource\Pages;

use App\Filament\Resources\RRHH\EmpleadoResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

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
    public function getTitle(): string|Htmlable
    {
        return ''; // Oculta el título por completo
    }
    
    // Redirigir al listado principal después de guardar
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    //Funcion para guardar el array de gps
    public function mutateFormDataBeforeSave(array $data): array
    {
        if (is_array($this->ubicacion_gps)) {
            $lat = round(floatval($this->ubicacion_gps['lat'] ?? 0), 6);
            $lng = round(floatval($this->ubicacion_gps['lng'] ?? 0), 6);

            // Si la ubicación es la predeterminada, guarda como null
            if ($lat == -16.500000 && $lng == -68.150000) {
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