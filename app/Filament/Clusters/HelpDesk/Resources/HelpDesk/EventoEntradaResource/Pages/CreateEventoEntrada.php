<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEventoEntrada extends CreateRecord
{
   protected static string $resource = EventoEntradaResource::class;
}