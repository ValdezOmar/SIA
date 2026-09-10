<?php

namespace App\Filament\Widgets\Concerns;

trait RendersDashboardSummaryCards
{
    /**
     * Expone las estadísticas de Filament a la vista Livewire personalizada.
     * Conserva la lógica de cada widget y evita el contenedor de StatsOverview.
     */
    public function dashboardCards(): array
    {
        return $this->getStats();
    }

    protected function getViewData(): array
    {
        return [
            'heading' => $this->getHeading(),
            'description' => $this->getDescription(),
            'cards' => $this->dashboardCards(),
            'pollingInterval' => $this->getPollingInterval(),
        ];
    }
}
