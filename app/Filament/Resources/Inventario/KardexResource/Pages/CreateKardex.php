<?php

namespace App\Filament\Resources\Inventario\KardexResource\Pages;

use App\Filament\Resources\Inventario\KardexResource;
use App\Models\Inventario\Kardex;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateKardex extends CreateRecord
{
    protected static string $resource = KardexResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['documento_tipo'] = $data['documento_tipo'] ?? 'manual';
        $data['documento_id'] = $data['documento_id'] ?? 0;
        $data['usuario_id'] = $data['usuario_id'] ?? auth()->id();
        $data['creado_por'] = $data['creado_por'] ?? auth()->id();
        $data['empresa_id'] = $data['empresa_id'] ?? auth()->user()?->empresa_id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $kardex = Kardex::registrarMovimiento($data);

        if (! $kardex->exists || ! Kardex::query()->whereKey($kardex->getKey())->exists()) {
            throw new RuntimeException('No se pudo confirmar la persistencia del movimiento en Kardex.');
        }

        return $kardex;
    }

    protected function getCreatedNotification(): ?Notification
    {
        /** @var Kardex $kardex */
        $kardex = $this->record->fresh(['articulo', 'almacen', 'asientoContable']);
        $articulo = $kardex->articulo?->nombre_comercial ?? 'Artículo no identificado';
        $almacen = $kardex->almacen?->nombre ?? 'Almacén no identificado';
        $cantidad = number_format((float) $kardex->cantidad, 2, ',', '.');
        $anterior = number_format((float) $kardex->cantidad_anterior, 2, ',', '.');
        $posterior = number_format((float) $kardex->cantidad_posterior, 2, ',', '.');
        $importe = number_format((float) $kardex->costo_total, 2, ',', '.');
        $accion = $kardex->direccion === 'entrada' ? 'Se incorporaron' : 'Se descontaron';
        $pendiente = (bool) data_get($kardex->datos_adicionales, 'contabilizacion_pendiente');
        $sinEfectoContable = in_array($kardex->tipo_movimiento, [
            'transferencia_entrada', 'transferencia_salida', 'consignacion',
        ], true);

        $estadoContable = match (true) {
            $pendiente => 'Contabilidad: pendiente de regularización. El inventario sí fue guardado.',
            $kardex->asientoContable !== null => "Contabilidad: asiento #{$kardex->asientoContable->id} confirmado.",
            $sinEfectoContable => 'Contabilidad: sin asiento; el movimiento no altera el patrimonio.',
            (float) $kardex->costo_total <= 0 => 'Contabilidad: sin asiento; el movimiento no tiene valor contable.',
            default => 'Contabilidad: sin asiento generado.',
        };

        return Notification::make()
            ->success()
            ->title("{$kardex->tipo_movimiento_label} registrada (#{$kardex->id})")
            ->body(implode("\n", [
                "Artículo: {$articulo}",
                "Almacén: {$almacen}",
                "Resultado: {$accion} {$cantidad} unidades. Stock: {$anterior} → {$posterior}.",
                "Valor del movimiento: Bs {$importe}.",
                $estadoContable,
            ]))
            ->persistent();
    }
}
