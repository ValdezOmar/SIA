<?php

namespace App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages;

use App\Filament\Resources\Inventario\TransferenciaAlmacenResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTransferenciaAlmacen extends CreateRecord
{
    protected static string $resource = TransferenciaAlmacenResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['estado'] = 'en_transito';
        $data['enviado_por'] = auth()->id();
        $data['fecha_envio'] = now();

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Solicitud de traspaso enviada';
    }

    protected function afterCreate(): void
    {
        $this->record->loadMissing(['receptor', 'almacenOrigen', 'almacenDestino']);

        if (! $this->record->receptor) {
            return;
        }

        Notification::make()
            ->title('Nueva solicitud de traspaso')
            ->body(sprintf(
                'La solicitud %s requiere su revisión. Origen: %s. Destino: %s. El stock no se moverá hasta que acepte el traspaso.',
                $this->record->codigo,
                $this->record->almacenOrigen?->nombre ?? 'No disponible',
                $this->record->almacenDestino?->nombre ?? 'No disponible',
            ))
            ->icon('heroicon-o-arrows-right-left')
            ->iconColor('warning')
            ->actions([
                Action::make('ver_traspasos')
                    ->label('Revisar solicitudes')
                    ->url(TransferenciaAlmacenResource::getUrl('index'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($this->record->receptor, isEventDispatched: true);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
