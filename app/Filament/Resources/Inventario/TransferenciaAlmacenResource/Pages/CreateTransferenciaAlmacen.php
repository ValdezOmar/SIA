<?php

namespace App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages;

use App\Filament\Resources\Inventario\TransferenciaAlmacenResource;
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
