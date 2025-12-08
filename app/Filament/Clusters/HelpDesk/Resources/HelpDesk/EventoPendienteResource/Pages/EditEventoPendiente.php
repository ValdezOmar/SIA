<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoPendienteResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoPendienteResource;
use App\Models\HelpDesk\Evento;
use App\Models\RRHH\Empleado;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

use Illuminate\Support\Facades\Auth;

class EditEventoPendiente extends EditRecord
{
    protected static string $resource = EventoPendienteResource::class;

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

            Action::make('derivar')
                ->label('Derivar')
                ->color('success')
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
                ])
                ->action(function (array $data, Evento $record) {
                    try {
                        // 1. GUARDAR EL FORMULARIO COMPLETO usando el método saveForm()
                        $this->callHook('beforeSave');
                        $this->form->model($record)->saveRelationships();
                        $record->save();

                        // 2. Refrescar para obtener datos actualizados
                        $record->refresh();

                        // 3. Realizar la derivación
                        $record->update([
                            'destinatario_id' => $data['destinatario_id'],
                            'estado' => 'salida',
                            'fecha_salida' => now(),
                        ]);

                        // 4. Crear nuevo registro para el destinatario
                        Evento::create([
                            'hd_ticket_id' => $record->hd_ticket_id,
                            'remitente_id' => $record->encargado_id,
                            'destinatario_id' => $data['destinatario_id'],
                            'area_origen_id' => $record->area_destino_id,
                            'area_destino_id' => Empleado::find($data['destinatario_id'])?->area_id,
                            'estado' => 'entrada',
                            'fecha_entrada' => now(),
                            'observacion' => $record->descripcion,
                            'prioridad' => $record->prioridad,

                        ]);

                        // 5. Actualizar ticket principal
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

                        return redirect(EventoPendienteResource::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al derivar')
                            ->body('Ocurrió un error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Derivar Ticket')
                ->modalDescription('¿Derivar este ticket a otro técnico?'),

            //Cerrar Ticket
            Action::make('cerrar')
                ->label('Cerrar Ticket')
                ->color('warning')
                ->icon('heroicon-o-check-circle')
                ->tooltip('Cerrar ticket')
                // ->form([
                //     Textarea::make('observaciones')
                //         ->label('Observaciones de cierre')
                //         ->placeholder('Describe la solución o motivo del cierre...')
                //         ->required(),
                // ])
                ->action(function (array $data, Evento $record) {
                    // 1. Cerrar el evento actual
                    $record->update([
                        'estado' => 'cerrado',
                        'fecha_salida' => now(),
                        // 'observaciones' => ($record->observaciones ? $record->observaciones . "\n" : "") .
                        //     "CERRADO: " . $data['observaciones'] . " - Por: " . Auth::user()->name . " - Fecha: " . now()->format('Y-m-d H:i'),
                    ]);

                    // 2. Cerrar el ticket principal
                    if ($record->ticket) {
                        $record->ticket->update([
                            'estado' => 'cerrado',
                            'fecha_cierre' => now(),
                        ]);
                    }

                    Notification::make()
                        ->title('Ticket Cerrado')
                        ->body('El ticket ha sido cerrado exitosamente.')
                        ->success()
                        ->send();

                    return redirect(EventoPendienteResource::getUrl('index'));
                })
                ->hidden(
                    fn(Evento $record): bool =>
                    $record->estado === 'cerrado' ||
                        !in_array($record->estado, ['pendiente', 'entrada', 'salida'])
                )
                ->requiresConfirmation()
                ->modalHeading('Cerrar Ticket')
                ->modalDescription('¿Estás seguro de cerrar este ticket? Esta acción no se puede deshacer.'),

            // Botón CANCELAR
            Action::make('cancelar')
                ->label('Cancelar')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->tooltip('Cancelar y volver a la lista')
                ->url(EventoPendienteResource::getUrl('index')),

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