<?php

namespace App\Filament\Resources\Contabilidad\CentroCostoResource\Pages;

use App\Filament\Resources\Contabilidad\CentroCostoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCentroCosto extends EditRecord
{
    protected static string $resource = CentroCostoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
