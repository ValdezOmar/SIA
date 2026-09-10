<?php

namespace App\Filament\Resources\Configuracion\UserResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Configuracion\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
