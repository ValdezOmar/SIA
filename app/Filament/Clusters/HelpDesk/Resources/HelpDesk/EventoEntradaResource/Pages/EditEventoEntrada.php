<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource;
use App\Models\RRHH\Empleado;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEventoEntrada extends EditRecord
{
    protected static string $resource = EventoEntradaResource::class;

    // Botones que aparecen al pie del formulario
    protected function getFormActions(): array
    {
        return [
            // Botón DERIVAR - aparece al pie
            Action::make('derivar')
                ->label('Derivar')
                ->icon('heroicon-o-arrow-right')
                ->color('primary')
                ->form([
                    Select::make('destinatario_id')
                        ->label('Derivar a:')
                        ->options(Empleado::where('activo', true)->pluck('nombres', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data, $record) {
                    // Cambia destinatario y estado
                    $record->update([
                        'destinatario_id' => $data['destinatario_id'],
                        'estado' => 'salida',
                        'fecha_salida' => now(),
                    ]);

                    // También actualizar el ticket relacionado
                    if ($record->ticket) {
                        $record->ticket->update([
                            'destinatario_id' => $data['destinatario_id'],
                            'estado' => 'salida',
                        ]);
                    }

                    Notification::make()
                        ->title('Ticket derivado correctamente')
                        ->success()
                        ->send();
                        
                    $this->redirect(EventoEntradaResource::getUrl('index'));
                }),

            // Botón CERRAR TICKET - aparece al pie
            Action::make('cerrar')
                ->label('Cerrar Ticket')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Cerrar Ticket')
                ->modalDescription('¿Está seguro de cerrar este ticket?. Este proceso es irreversible y se dara por concluido el proceso.')
                ->modalSubmitActionLabel('Sí, cerrar definitivamente')
                ->modalCancelActionLabel('Cancelar')
                ->action(function ($record) {
                    $record->update([
                        'estado' => 'cerrado',
                        'fecha_salida' => now(),
                    ]);

                    if ($record->ticket) {
                        $record->ticket->update([
                            'estado' => 'cerrado',
                        ]);
                    }

                    Notification::make()
                        ->title('Ticket cerrado correctamente')
                        ->success()
                        ->send();
                        
                    $this->redirect(EventoEntradaResource::getUrl('index'));
                }),
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