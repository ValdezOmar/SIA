<?php

namespace App\Filament\Resources\Almacen\InventarioResource\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use App\Filament\Resources\Almacen\InventarioResource\Pages\ListInventarios;

class InventarioStats extends Widget
{
    use InteractsWithPageTable;

    protected static string $view = 'filament.widgets.inventario-stats';
    protected static ?string $pollingInterval = null; // Desactivamos polling ya que usaremos eventos

    // Propiedades reactivas
    public int $totalItems = 0;
    public int $itemsContados = 0;
    public int $itemsNoContados = 0;
    public int $porcentajeContados = 0;
    public int $diferenciaTotal = 0;
    public string $progressColor = '';

    protected function getTablePage(): string
    {
        return ListInventarios::class;
    }

    protected function getListeners(): array
    {
        return [
            'refreshWidget' => '$refresh',
            'updateFilters' => 'updateStats',
        ];
    }

    public function mount(): void
    {
        $this->updateStats();
    }

    public function updateStats(): void
    {
        // Obtener query con los filtros actuales
        $query = $this->getPageTableQuery();

        // Calcular estadísticas
        $this->totalItems = $query->count();
        $this->itemsContados = $query->whereNotNull('saldo_contado')->count();
        $this->itemsNoContados = $this->totalItems - $this->itemsContados;
        $this->porcentajeContados = $this->totalItems > 0 ? round(($this->itemsContados / $this->totalItems) * 100) : 0;
        $this->diferenciaTotal = $this->getDiferenciaTotal($query);
        $this->progressColor = $this->getProgressColor($this->porcentajeContados);
    }

    protected function getDiferenciaTotal($query): int
    {
        return $query->clone()
            ->whereNotNull('saldo_contado')
            ->get()
            ->sum(fn($item) => abs($item->saldo_actual - $item->saldo_contado));
    }

    protected function getProgressColor(int $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'bg-green-500',
            $percentage >= 50 => 'bg-yellow-500',
            default => 'bg-red-500',
        };
    }
}