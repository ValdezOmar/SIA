<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk\TicketResource\Pages;

use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\TicketResource;
use App\Models\HelpDesk\Evento;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function afterCreate(): void
    {
        // $this->data contiene todos los datos del formulario
        $destinatarioId = $this->data['destinatario_id'] ?? 1;

        // Crear el evento inicial y mantenerlo cerrado para seguimiento de historial
        Evento::create([
            'hd_ticket_id' => $this->record->id,
            'remitente_id' => Auth::user()->empleado?->id ?? 1,
            'destinatario_id' => $destinatarioId,
            'estado' => 'atendido',
            'fecha_salida' => now(),
            'observaciones' => 'Creacion del ticket y derivado por sistema',
            'prioridad' => $this->record->prioridad,
        ]);
        // Crear el evento después de crear el ticket
        // Evento entrada con created_at +1 segundo
        $adjuntosRemitente = [];

        if (isset($this->data['adjunto']) && is_array($this->data['adjunto'])) {
            // Filament ya ha procesado los archivos y los guardó
            // Solo guardamos las rutas, no los archivos temporales
            foreach ($this->data['adjunto'] as $adjunto) {
                if (is_string($adjunto) && !empty($adjunto)) {
                    $adjuntosRemitente[] = $adjunto;
                }
            }
        }
         DB::table('hd_eventos')->insert([
            'hd_ticket_id'   => $this->record->id,
            'remitente_id'   => Auth::user()->empleado?->id ?? 1,
            'destinatario_id' => $destinatarioId,
            'estado'         => 'entrada',
            'fecha_entrada'  => now()->addSecond(),
            'prioridad'      => $this->record->prioridad,
            'adjunto_remitente' => !empty($adjuntosRemitente) ? json_encode($adjuntosRemitente) : null,
            'created_at'     => now()->addSecond(),
            'updated_at'     => now()->addSecond(),
        ]);

        // Evento::create([
        //     'hd_ticket_id' => $this->record->id,
        //     'remitente_id' => Auth::user()->empleado?->id ?? 1,
        //     'destinatario_id' => $destinatarioId,
        //     'estado' => 'entrada',
        //     'fecha_entrada' => now()->addSeconds(2),
        //     'prioridad' => $this->record->prioridad,
        //     'adjunto_remitente' => !empty($adjuntosRemitente) ? $adjuntosRemitente : null,
        //     'observaciones' => 'Ticket creado con adjuntos del remitente',
        //     'created_at' => now()->addSeconds(2),
        //     'updated_at' => now()->addSeconds(2),
        // ]);
    }

    // Redirigir al index después de crear
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}