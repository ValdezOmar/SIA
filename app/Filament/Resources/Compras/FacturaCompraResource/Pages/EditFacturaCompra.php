<?php

namespace App\Filament\Resources\Compras\FacturaCompraResource\Pages;

use App\Filament\Resources\Compras\FacturaCompraResource;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditFacturaCompra extends EditRecord
{
    protected static string $resource = FacturaCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('anularFactura')
                ->label('Anular factura')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Anular factura')
                ->modalDescription('Se anularán sus pagos y asientos. No estará disponible si ya se procesó el ingreso a inventario.')
                ->form([
                    Textarea::make('motivo')->label('Motivo de la anulación')->required()->maxLength(2000),
                ])
                ->action(function (array $data): void {
                    $this->record->anularDocumento($data['motivo']);
                    Notification::make()->title('Factura anulada')->success()->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record->estado !== 'anulada'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return ((float) $this->record->monto_pagado > 0 || in_array($this->record->estado, ['pagada', 'anulada'], true))
            ? []
            : parent::getFormActions();
    }
}
