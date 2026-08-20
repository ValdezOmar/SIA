<?php

namespace App\Filament\Resources\Inventario\KardexResource\Pages;

use App\Filament\Resources\Inventario\KardexResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKardex extends CreateRecord
{
    protected static string $resource = KardexResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['documento_tipo'] = $data['documento_tipo'] ?? 'manual';
        $data['documento_id'] = $data['documento_id'] ?? 0;
        $data['usuario_id'] = $data['usuario_id'] ?? auth()->id();
        $data['creado_por'] = $data['creado_por'] ?? auth()->id();
        $data['empresa_id'] = $data['empresa_id'] ?? auth()->user()?->empresa_id;

        return $data;
    }
}
