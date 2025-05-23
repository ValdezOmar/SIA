<?php

namespace App\Filament\Resources\RRHH\AsistenciaResource\Pages;

use App\Filament\Resources\RRHH\AsistenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use App\Models\RRHH\Empleado;

class ListAsistencias extends ListRecords
{
    protected static string $resource = AsistenciaResource::class;
    public ?string $localizacion = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false) // Esto desactiva el botón "Crear y crear otro"
                ->mutateFormDataUsing(function (array $data): array {
                    // Obtener el CI del empleado asociado al usuario autenticado
                    $user = Auth::user();
                    $empleado = Empleado::where('correo_corporativo', $user->email)->first();
                    // Si viene localización del formulario, la guardamos
                    if ($this->localizacion) {
                        $data['localizacion'] = $this->localizacion;
                    }
                    // Establecemos el valor por defecto para id_equipo
                    $data['id_equipo'] = 'REMOTO';
                    // Asignar el CI del empleado
                    $data['user_id'] = $empleado ? $empleado->ci : null; 
                    return $data;
                }),
        ];
    }
}
