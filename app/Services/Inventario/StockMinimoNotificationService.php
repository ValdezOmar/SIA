<?php

namespace App\Services\Inventario;

use App\Filament\Resources\Inventario\StockAlmacenResource;
use App\Models\Inventario\Existencia;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockMinimoNotificationService
{
    /**
     * Notifica a los usuarios de la sucursal cuando la disponibilidad cruza el mínimo.
     *
     * @param  array<string, mixed>|null  $valoresAnteriores
     */
    public function notificarSiAlcanzaMinimo(Existencia $existencia, ?array $valoresAnteriores = null): void
    {
        $existencia->loadMissing(['articulo', 'almacen.sucursal']);

        $minimo = (float) $existencia->cantidad_minima;
        $disponible = (float) $existencia->stock_disponible;

        if ($minimo <= 0 || $disponible > $minimo) {
            return;
        }

        if ($valoresAnteriores !== null) {
            $disponibleAnterior = (float) ($valoresAnteriores['cantidad_disponible'] ?? $existencia->cantidad_disponible)
                - (float) ($valoresAnteriores['cantidad_comprometida'] ?? $existencia->cantidad_comprometida);
            $minimoAnterior = (float) ($valoresAnteriores['cantidad_minima'] ?? $existencia->cantidad_minima);

            if ($minimoAnterior > 0 && $disponibleAnterior <= $minimoAnterior) {
                return;
            }
        }

        $almacen = $existencia->almacen;
        $sucursal = $almacen?->sucursal;

        if (! $almacen || ! $sucursal) {
            return;
        }

        $usuarios = $this->usuariosActivosDeSucursal((int) $sucursal->getKey());

        if ($usuarios->isEmpty()) {
            return;
        }

        $articulo = $existencia->articulo;
        $nombreArticulo = $articulo?->nombre_comercial ?: $articulo?->descripcion ?: 'Artículo';
        $codigoArticulo = $articulo?->codigo ? " ({$articulo->codigo})" : '';

        Notification::make()
            ->title("Poco stock: {$nombreArticulo}")
            ->body(sprintf(
                '%s. Sucursal: %s. Almacén: %s. Disponible: %s unidades; mínimo configurado: %s. Revisa la reposición.',
                trim($nombreArticulo.$codigoArticulo),
                $sucursal->nombre,
                $almacen->nombre,
                $this->cantidad($disponible),
                $this->cantidad($minimo),
            ))
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('warning')
            ->persistent()
            ->actions([
                Action::make('ver_stock')
                    ->label('Ver existencias')
                    ->url(StockAlmacenResource::getUrl('index'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($usuarios, isEventDispatched: true);
    }

    /** @return Collection<int, User> */
    private function usuariosActivosDeSucursal(int $sucursalId): Collection
    {
        return User::query()
            ->whereHas('empleado', fn (Builder $empleado): Builder => $empleado
                ->where('rh_empleados.activo', true)
                ->whereHas('historialActivo', fn (Builder $historial): Builder => $historial
                    ->where('sucursal_id', $sucursalId)
                    ->where('activo', true)))
            ->get();
    }

    private function cantidad(float $cantidad): string
    {
        return rtrim(rtrim(number_format($cantidad, 2, ',', '.'), '0'), ',');
    }
}
