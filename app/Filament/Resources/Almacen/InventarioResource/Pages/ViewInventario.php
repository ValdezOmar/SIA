<?php

namespace App\Filament\Resources\Almacen\InventarioResource\Pages;

use App\Filament\Resources\Almacen\InventarioResource;
use App\Services\Inventario\InventarioFisicoService as Servicio;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewInventario extends ViewRecord
{
    protected static string $resource = InventarioResource::class;

    #[On('inventario-actualizado')]
    public function actualizar(): void
    {
        $this->record = InventarioResource::getEloquentQuery()->findOrFail($this->record->id);
    }

    public function getSubheading(): ?string
    {
        return 'El conteo registra existencias físicas, incluidas las unidades reservadas. El cierre documenta la auditoría; cualquier ajuste de stock debe registrarse por Kardex con este código como referencia.';
    }

    protected function getHeaderActions(): array
    {
        $programador = fn () => auth()->user()->can(Servicio::PROGRAMAR);
        $acciones = [
            Action::make('exportarPdf')->label('Descargar PDF')->icon('heroicon-o-document-arrow-down')
                ->action(fn () => app(\App\Services\Inventario\InventarioPdfService::class)->descargar($this->record)),
            Action::make('iniciar')->label('Iniciar conteo')->icon('heroicon-o-play')->requiresConfirmation()
                ->modalDescription('Se guardará el stock de referencia de este almacén. Coordine la pausa de movimientos durante el conteo.')
                ->visible(fn () => $programador() && $this->record->estado === 'programado')
                ->action(function () {
                    app(Servicio::class)->iniciar($this->record);
                    $this->terminado();
                }),
            Action::make('revision')->label('Enviar a revisión')->icon('heroicon-o-clipboard-document-check')->requiresConfirmation()
                ->visible(fn () => auth()->user()->can(Servicio::CONTAR) && $this->record->estado === 'en_conteo')
                ->action(function () {
                    app(Servicio::class)->enviarRevision($this->record);
                    $this->terminado();
                }),
            Action::make('exportar')->label('Descargar conteos')->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    abort_unless(InventarioResource::canView($this->record), 403);

                    return response()->streamDownload(function () {
                        $archivo = fopen('php://output', 'w');
                        fwrite($archivo, "\xEF\xBB\xBF");
                        fputcsv($archivo, ['Inventario', 'Código', 'Artículo', 'Unidad', 'Stock referencia', 'Reservado', 'Contado', 'Diferencia', 'Contado por', 'Fecha conteo', 'Observaciones', 'Revisado por', 'Conclusión'], ';');
                        foreach ($this->record->conteos()->with(['contador', 'revisor'])->orderBy('codigo')->lazy(200) as $linea) {
                            $valores = [$this->record->codigo, $linea->codigo, $linea->nombre, $linea->unidad, $linea->stock_sistema,
                                $linea->stock_reservado, $linea->cantidad_contada, $linea->diferencia, $linea->contador?->name,
                                $linea->contado_at, $linea->observaciones, $linea->revisor?->name, $linea->nota_revision];
                            fputcsv($archivo, array_map(fn ($valor) => is_string($valor) && preg_match('/^[=+@\-\t\r\n]/', $valor) ? "'".$valor : $valor, $valores), ';');
                        }
                        fclose($archivo);
                    }, $this->record->codigo.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
        ];
        foreach (['cerrar' => 'Cerrar inventario', 'devolver' => 'Devolver a conteo', 'cancelar' => 'Cancelar inventario'] as $accion => $label) {
            $acciones[] = Action::make($accion)->label($label)->color($accion === 'cancelar' ? 'danger' : 'warning')
                ->form([Textarea::make('motivo')->label('Motivo / conclusión de auditoría')->required()->maxLength(4000)])
                ->visible(fn () => $programador() && ($accion === 'cancelar'
                    ? in_array($this->record->estado, ['programado', 'en_conteo', 'en_revision']) : $this->record->estado === 'en_revision'))
                ->action(function (array $data) use ($accion) {
                    app(Servicio::class)->finalizar($this->record, $accion, $data['motivo']);
                    $this->terminado();
                });
        }

        return $acciones;
    }

    private function terminado(): void
    {
        Notification::make()->title('Inventario actualizado')->success()->send();
        $this->redirect(InventarioResource::getUrl('view', ['record' => $this->record]));
    }
}
