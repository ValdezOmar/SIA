<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource;
use App\Models\HelpDesk\Evento;
use App\Models\RRHH\Empleado;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEventoEntrada extends EditRecord
{
    protected static string $resource = EventoEntradaResource::class;

    // Botones que aparecen al pie del formulario
    protected function getFormActions(): array
    {
        return [
            //Boton aceptar ticket
            Action::make('aceptar')
                ->label('Aceptar')
                ->color('primary')
                ->icon('heroicon-o-check-circle')
                ->tooltip('Aceptar ticket')
                ->action(function (Evento $record) {
                    // Obtener el ID del empleado actual
                    $empleadoId = Auth::user()->empleado?->id;

                    // 1. Buscar y cerrar el registro anterior (si existe)
                    // Buscamos el registro donde este empleado era el DESTINATARIO
                    // y el estado era 'entrada' o 'salida'
                    if ($record->ticket) {
                        Evento::where('hd_ticket_id', $record->hd_ticket_id)
                            ->where('destinatario_id', $empleadoId) // Este empleado era el destinatario
                            ->whereIn('estado', ['entrada', 'salida']) // En estado pendiente de aceptación
                            ->where('id', '!=', $record->id) // Excluir el registro actual
                            ->update([
                                'estado' => 'cerrado',
                                'fecha_salida' => now(),
                            ]);
                    }

                    // 2. Actualizar el registro actual (aceptar en la bandeja)
                    $record->update([
                        'encargado_id' => $empleadoId,
                        'destinatario_id' => null, // Ya no tiene destinatario, lo tiene el encargado
                        'estado' => 'pendiente',
                        'fecha_recepcion' => now(), // Fecha en que se aceptó
                    ]);

                    // 3. Actualizar el ticket principal
                    if ($record->ticket) {
                        $record->ticket->update([
                            'estado' => 'en_proceso',
                            'encargado_id' => $empleadoId,
                        ]);
                    }

                    Notification::make()
                        ->title('Ticket Aceptado')
                        ->body('El ticket ha sido aceptado y está ahora en proceso.')
                        ->success()
                        ->send();
                })
                ->hidden(fn(Evento $record): bool => !is_null($record->encargado_id))
                ->requiresConfirmation()
                ->modalHeading('Aceptar Ticket')
                ->modalDescription('¿Aceptar este ticket para comenzar a trabajar en él?'),

            //Boton derivar ticket con funcionalidades exclusivas para bandeja de entrada
            Action::make('derivar')
                ->label('Derivar')
                ->color('warning')
                ->icon('heroicon-o-arrow-up-circle')
                ->tooltip('Derivar a otro técnico')
                ->form([
                    Select::make('destinatario_id')
                        ->label('Derivar a:')
                        ->options(
                            Empleado::where('activo', true)
                                ->get()
                                ->mapWithKeys(fn($emp) => [
                                    $emp->id => "{$emp->full_name}" .
                                        ($emp->cargo ? " - {$emp->cargo}" : "")
                                ])
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Textarea::make('descripcion')
                        ->label('Observaciones')
                        ->placeholder('Describe por qué se deriva este ticket...')
                        ->required(),
                ])
                ->action(function (array $data, Evento $record) {
                    $destinatarioActual = $record->destinatario_id;
                    $record->update([
                        'remitente_id' => $destinatarioActual,
                        'encargado_id' => $destinatarioActual,
                        'destinatario_id' => $data['destinatario_id'],
                        'estado' => 'salida',
                        'fecha_salida' => now(),
                    ]);

                    Evento::create([
                        'hd_ticket_id' => $record->hd_ticket_id,
                        'remitente_id' => $record->encargado_id,
                        'destinatario_id' => $data['destinatario_id'],
                        'area_origen_id' => $record->area_destino_id,
                        'area_destino_id' => Empleado::find($data['destinatario_id'])?->area_id,
                        'estado' => 'entrada',
                        'fecha_entrada' => now(),
                        'observaciones' => $data['descripcion'],
                        'prioridad' => $record->prioridad,
                    ]);

                    if ($record->ticket) {
                        $record->ticket->update([
                            'destinatario_id' => $data['destinatario_id'],
                            'estado' => 'en_proceso',
                        ]);
                    }

                    Notification::make()
                        ->title('Ticket Derivado')
                        ->body('El ticket ha sido derivado a otro técnico.')
                        ->success()
                        ->send();

                    return redirect(EventoEntradaResource::getUrl('index'));
                })
                ->requiresConfirmation()
                ->modalHeading('Derivar Ticket')
                ->modalDescription('¿Derivar este ticket a otro técnico?')
                ->hidden(fn(Evento $record): bool => !is_null($record->observaciones)), // SOLO SE MUESTRA SI OBSERVACIONES ES NULL

            // Botón CANCELAR
            Action::make('cancelar')
                ->label('Cancelar')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->tooltip('Cancelar y volver a la lista')
                ->url(EventoEntradaResource::getUrl('index')),

        ];
    }

    // Botones que aparecen en la cabecera
    protected function getHeaderActions(): array
    {
        return [
            //Vacio para que no aparezca ningun boton
        ];
    }
}