<?php

namespace App\Filament\Resources\Inventario\KardexResource\Pages;

use App\Filament\Resources\Inventario\KardexResource;
use App\Services\Contabilidad\RegularizacionKardexService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListKardexes extends ListRecords
{
    protected static string $resource = KardexResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regularizar_contabilidad')
                ->label('Regularizar contabilidad')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Verificación y regularización de Kardex')
                ->modalDescription('Revise primero la integridad. Los errores bloqueantes deben corregirse antes de crear asientos.')
                ->modalSubmitActionLabel('Regularizar pendientes')
                ->schema([
                    Placeholder::make('diagnostico_integridad')
                        ->label('Diagnóstico previo')
                        ->content(fn (): HtmlString => $this->diagnosticoHtml(
                            app(RegularizacionKardexService::class)->diagnosticar(auth()->user()?->empresa_id),
                        ))
                        ->columnSpanFull(),
                    DatePicker::make('fecha_contable')
                        ->label('Fecha contable de regularización')
                        ->default(now())
                        ->required()
                        ->native()
                        ->extraInputAttributes(['lang' => 'es-BO'])
                        ->helperText('Puede escribir, pegar o elegir la fecha en el calendario. El período debe estar abierto.'),
                ])
                ->action(function (array $data): void {
                    $servicio = app(RegularizacionKardexService::class);
                    $diagnostico = $servicio->diagnosticar(auth()->user()?->empresa_id);

                    if (! $diagnostico['integro']) {
                        Notification::make()
                            ->danger()
                            ->title("Regularización bloqueada: {$diagnostico['bloqueantes']} inconsistencias")
                            ->body('Corrija los Kardex sin movimiento de inventario, las referencias inexistentes y los asientos desbalanceados. Abra el diagnóstico nuevamente para verificar que el total bloqueante sea cero.')
                            ->persistent()
                            ->send();

                        return;
                    }

                    $resultado = $servicio->ejecutar(
                        Carbon::parse($data['fecha_contable'])->startOfDay(),
                        auth()->user()?->empresa_id,
                    );
                    $mensaje = "Generados: {$resultado['contabilizados']}. Ya cubiertos: {$resultado['cubiertos']}. Sin efecto contable: {$resultado['sin_efecto']}. Sin valor: {$resultado['sin_valor']}. Errores: ".count($resultado['errores']).'.';
                    if ($resultado['errores']) {
                        $mensaje .= ' '.collect($resultado['errores'])->take(5)
                            ->map(fn (array $error) => "Kardex #{$error['kardex_id']}: {$error['mensaje']}")
                            ->implode(' | ');
                    }
                    $notificacion = Notification::make()
                        ->title(empty($resultado['errores']) ? 'Regularización completada' : 'Regularización completada con observaciones')
                        ->body($mensaje)
                        ->persistent();
                    empty($resultado['errores']) ? $notificacion->success() : $notificacion->warning();
                    $notificacion->send();
                }),
            Action::make('crear_movimiento')
                ->label('Registrar movimiento')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(KardexResource::getUrl('create')),
        ];
    }

    private function diagnosticoHtml(array $diagnostico): HtmlString
    {
        $estado = $diagnostico['integro']
            ? '<strong class="text-success-700 dark:text-success-300">Integridad aprobada.</strong> Puede regularizar los pendientes contables.'
            : '<strong class="text-danger-700 dark:text-danger-300">Se encontraron '.$diagnostico['bloqueantes'].' inconsistencias bloqueantes.</strong> Corríjalas antes de continuar.';

        return new HtmlString('<div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">'
            .$estado
            .'<dl class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">'
            .'<div><dt class="text-gray-500">Kardex confirmados</dt><dd class="font-bold">'.$diagnostico['confirmados'].'</dd></div>'
            .'<div><dt class="text-gray-500">Pendientes contables</dt><dd class="font-bold text-warning-700">'.$diagnostico['pendientes_contables'].'</dd></div>'
            .'<div><dt class="text-gray-500">Sin movimiento de inventario</dt><dd class="font-bold text-danger-700">'.$diagnostico['sin_movimiento_inventario'].'</dd></div>'
            .'<div><dt class="text-gray-500">Asientos desbalanceados</dt><dd class="font-bold text-danger-700">'.$diagnostico['asientos_desbalanceados'].'</dd></div>'
            .'<div><dt class="text-gray-500">Ventas sin factura</dt><dd class="font-bold text-danger-700">'.$diagnostico['ventas_sin_factura'].'</dd></div>'
            .'<div><dt class="text-gray-500">Compras sin factura</dt><dd class="font-bold text-danger-700">'.$diagnostico['compras_sin_factura'].'</dd></div></dl>'
            .'<p class="mt-3 text-gray-600 dark:text-gray-300"><strong>Acciones:</strong> revise los Kardex sin reflejo de inventario; corrija o anule referencias inexistentes; balancee los asientos. Cuando los bloqueantes sean 0, cree solo los asientos pendientes.</p></div>');
    }
}
