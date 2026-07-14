<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\Pages;

use App\Filament\Resources\Inventario\StockAlmacenResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

class EditStockAlmacens extends EditRecord
{
    protected static string $resource = StockAlmacenResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Ocultar el botón de Guardar
     */
    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->hidden();
    }

    /**
     * Modificar el botón de Cancelar
     */
    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Volver')
            ->url($this->getResource()::getUrl('index'));
    }

    /**
     * Deshabilitar todos los campos en el formulario
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // No redirigir, solo devolver los datos
        return $data;
    }

    /**
     * Evitar que se guarde
     */
    protected function beforeSave(): void
    {
        // Redirigir sin guardar usando un redirect normal
        $this->redirect($this->getResource()::getUrl('index'));
    }

    /**
     * Deshabilitar la actualización
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Redirigir sin guardar
        $this->redirect($this->getResource()::getUrl('index'));
        return $data;
    }
}