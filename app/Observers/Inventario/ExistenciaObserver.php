<?php

namespace App\Observers\Inventario;

use App\Models\Inventario\Existencia;
use App\Services\Inventario\StockMinimoNotificationService;

class ExistenciaObserver
{
    public function created(Existencia $existencia): void
    {
        app(StockMinimoNotificationService::class)->notificarSiAlcanzaMinimo($existencia);
    }

    public function updated(Existencia $existencia): void
    {
        app(StockMinimoNotificationService::class)->notificarSiAlcanzaMinimo(
            $existencia,
            $existencia->getPrevious(),
        );
    }
}
