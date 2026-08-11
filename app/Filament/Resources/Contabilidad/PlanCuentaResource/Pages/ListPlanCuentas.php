<?php

namespace App\Filament\Resources\Contabilidad\PlanCuentaResource\Pages;

use App\Filament\Resources\Contabilidad\PlanCuentaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanCuentas extends ListRecords
{
    protected static string $resource = PlanCuentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
