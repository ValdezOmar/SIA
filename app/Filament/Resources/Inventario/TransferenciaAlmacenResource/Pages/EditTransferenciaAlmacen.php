<?php

namespace App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages;

use App\Filament\Resources\Inventario\TransferenciaAlmacenResource;
use App\Models\Inventario\TransferenciaAlmacen;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTransferenciaAlmacen extends EditRecord
{
    protected static string $resource = TransferenciaAlmacenResource::class;

    protected function getHeaderActions(): array
    {
        /** @var TransferenciaAlmacen $transferencia */
        $transferencia = $this->record;

        return [
            Actions\Action::make('enviar')
                ->label('Enviar al destino')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => $transferencia->estado === 'borrador')
                ->requiresConfirmation()
                ->modalHeading('Enviar transferencia')
                ->modalDescription('Se descontará el stock del almacén origen y el receptor asignado deberá aprobar la llegada.')
                ->action(function () use ($transferencia): void {
                    $transferencia->enviar();
                    Notification::make()->title('Transferencia enviada')->body('El stock quedó en tránsito hasta que el receptor lo apruebe.')->success()->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            Actions\Action::make('recibir')
                ->label('Aprobar recepción')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $transferencia->estado === 'en_transito' && (int) $transferencia->receptor_id === (int) auth()->id())
                ->requiresConfirmation()
                ->modalHeading('Confirmar recepción')
                ->modalDescription('Verifique físicamente los artículos. Esta acción ingresará el stock al almacén destino.')
                ->action(function () use ($transferencia): void {
                    $transferencia->recibir();
                    Notification::make()->title('Recepción aprobada')->body('El stock y su trazabilidad se registraron en el almacén destino.')->success()->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            Actions\Action::make('rechazar')
                ->label('Rechazar y devolver al origen')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $transferencia->estado === 'en_transito' && (int) $transferencia->receptor_id === (int) auth()->id())
                ->form([Textarea::make('motivo')->label('Motivo del rechazo')->required()->maxLength(2000)->helperText('Explique la diferencia o incidencia encontrada.')])
                ->action(function (array $data) use ($transferencia): void {
                    $transferencia->rechazar($data['motivo']);
                    Notification::make()->title('Transferencia rechazada')->body('El stock fue devuelto al almacén de origen.')->success()->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            Actions\DeleteAction::make()->visible(fn () => $transferencia->estado === 'borrador'),
        ];
    }

    protected function getFormActions(): array
    {
        return $this->record->estado === 'borrador' ? parent::getFormActions() : [];
    }
}
