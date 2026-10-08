<?php

namespace App\Filament\Clusters\Sistema\Resources\AuditoriaResource\Pages;

use App\Filament\Clusters\Sistema\Resources\AuditoriaResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditorias extends ListRecords
{
    protected static string $resource = AuditoriaResource::class;

    public function getSubheading(): ?string
    {
        return 'Bitácora de solo lectura. Cambios de modelos y escrituras SQL pueden describir la misma operación. Use el ID de petición para relacionar sus efectos. Los campos sensibles se ocultan.';
    }
}
