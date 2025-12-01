<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipo extends EditRecord
{
    protected static string $resource = EquipoResource::class;
    public ?array $ubicacion_gps;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
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

    // Redirigir al index después de crear
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
}