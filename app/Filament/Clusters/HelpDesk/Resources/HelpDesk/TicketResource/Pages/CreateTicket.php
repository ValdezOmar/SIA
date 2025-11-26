<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\TicketResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\TicketResource;
use App\Models\HelpDesk\Evento;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;
    protected function afterCreate(): void
    {
        // $this->data contiene todos los datos del formulario
        $destinatarioId = $this->data['destinatario_id'] ?? 1;

        // Crear el evento después de crear el ticket
        Evento::create([
            'hd_ticket_id' => $this->record->id,
            'remitente_id' => Auth::user()->empleado?->id ?? 1,
            'destinatario_id' => $destinatarioId,
            'estado' => 'entrada',
            'fecha_entrada' => now(),
            'descripcion' => $this->record->diagnostico,
            'prioridad' => $this->record->prioridad,
        ]);
    }
    
    // Redirigir al index después de crear
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}