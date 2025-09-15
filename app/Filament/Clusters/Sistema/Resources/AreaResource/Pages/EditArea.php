<?php

namespace App\Filament\Clusters\Sistema\Resources\AreaResource\Pages;

use App\Filament\Clusters\Sistema\Resources\AreaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArea extends EditRecord
{
    protected static string $resource = AreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
