<?php

namespace App\Filament\Clusters\Sistema\Resources\ParametroResource\Pages;

use App\Filament\Clusters\Sistema\Resources\ParametroResource;
use App\Models\Sistema\Parametro;
use Filament\Resources\Pages\EditRecord;

class ManageParametro extends EditRecord
{
    protected static string $resource = ParametroResource::class;

    public function mount($record = null): void
    {
        // Siempre forzar el primer registro
        $this->record = Parametro::firstOrCreate([]);
        parent::mount($this->record->getKey());
    }
}