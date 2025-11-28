<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateEquipo extends CreateRecord
{
    protected static string $resource = EquipoResource::class;
    // Esta propiedad debe coincidir con el nombre del campo
    public ?array $ubicacion_gps;

    public function mutateFormDataBeforeSave(array $data): array
    {
        // Debug más detallado
        Log::info('=== DEBUG GPS DATA ===');
        Log::info('Property ubicacion_gps:', $this->ubicacion_gps ?? ['null']);
        Log::info('Form data ubicacion_gps:', $data['ubicacion_gps'] ?? ['null']);
        Log::info('======================');

        // Usar directamente la propiedad de la página
        if (is_array($this->ubicacion_gps) && 
            isset($this->ubicacion_gps['lat']) && 
            isset($this->ubicacion_gps['lng'])) {
            
            $lat = round(floatval($this->ubicacion_gps['lat']), 6);
            $lng = round(floatval($this->ubicacion_gps['lng']), 6);

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

        Log::info('Datos finales a guardar:', $data['ubicacion_gps'] ?? ['null']);

        return $data;
    }
}