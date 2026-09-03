<?php

namespace App\Filament\Resources\Inventario\KardexResource\Pages;

use App\Filament\Resources\Inventario\KardexResource;
use App\Services\Contabilidad\RegularizacionKardexService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListKardexes extends ListRecords
{
    protected static string $resource = KardexResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('regularizar_contabilidad')
                ->label('Regularizar contabilidad')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regularizar Kardex históricos')
                ->modalDescription('Se revisarán todos los movimientos confirmados de la empresa. Los asientos existentes y los generados por facturas no se duplicarán.')
                ->form([
                    DatePicker::make('fecha_contable')
                        ->label('Fecha contable de regularización')
                        ->default(now())
                        ->required()
                        ->native()
                        ->extraInputAttributes(['lang' => 'es-BO'])
                        ->helperText('Puede escribir, pegar o elegir la fecha en el calendario. El período debe estar abierto.'),
                ])
                ->action(function (array $data): void {
                    $resultado = app(RegularizacionKardexService::class)->ejecutar(
                        Carbon::parse($data['fecha_contable'])->startOfDay(),
                        auth()->user()?->empresa_id,
                    );

                    $mensaje = "Generados: {$resultado['contabilizados']}. Ya cubiertos: {$resultado['cubiertos']}. Sin efecto contable: {$resultado['sin_efecto']}. Sin valor: {$resultado['sin_valor']}. Errores: ".count($resultado['errores']).'.';
                    if ($resultado['errores']) {
                        $muestra = collect($resultado['errores'])->take(5)
                            ->map(fn (array $error) => "Kardex #{$error['kardex_id']}: {$error['mensaje']}")
                            ->implode(' | ');
                        $mensaje .= ' '.$muestra;
                    }
                    $notificacion = Notification::make()
                        ->title(empty($resultado['errores']) ? 'Regularización completada' : 'Regularización completada con observaciones')
                        ->body($mensaje)
                        ->persistent();

                    empty($resultado['errores']) ? $notificacion->success() : $notificacion->warning();
                    $notificacion->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
