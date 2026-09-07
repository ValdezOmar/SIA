<?php

namespace App\Filament\Resources\Almacen\InventarioResource\RelationManagers;

use App\Models\Inventario\InventarioConteo;
use App\Services\Inventario\InventarioFisicoService as Servicio;
use DesignTheBox\BarcodeField\Forms\Components\BarcodeInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConteosRelationManager extends RelationManager
{
    protected static string $relationship = 'conteos';

    protected static ?string $title = 'Conteo de artículos';

    public function isReadOnly(): bool
    {
        return false;
    }

    private function puedeContar(): bool
    {
        return auth()->user()->can(Servicio::CONTAR) && $this->getOwnerRecord()->fresh()->estado === 'en_conteo';
    }

    public function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['contador', 'revisor', 'articulo.fabricante']))
            ->description('Registre la cantidad física total de cada artículo. Escanear localiza el producto; no suma unidades automáticamente. Las series identifican el artículo, el conteo se realiza por artículo y almacén.')
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable()->copyable(),
                TextColumn::make('nombre')->label('Artículo')->searchable()->wrap()
                    ->description(fn ($record) => trim(($record->articulo?->codigo_alterno ?? '').' · '.($record->articulo?->fabricante?->nombre ?? ''), ' ·')),
                TextColumn::make('unidad')->label('Unidad'),
                TextColumn::make('stock_sistema')->label('Stock referencia')->numeric(2),
                TextColumn::make('cantidad_contada')->label('Contado')->numeric(2)->placeholder('Pendiente'),
                TextColumn::make('diferencia')->label('Diferencia')->numeric(2)->placeholder('Sin conteo')
                    ->color(fn ($state) => (float) $state === 0.0 ? 'success' : 'warning'),
                TextColumn::make('estado_conteo')->label('Estado')->badge()
                    ->getStateUsing(fn ($record) => $record->revisado_at ? 'Revisado' : ($record->cantidad_contada === null ? 'Pendiente' : 'Contado')),
                TextColumn::make('contador.name')->label('Contado por')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('contado_at')->label('Fecha conteo')->dateTime('d/m/Y H:i')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('observaciones')->label('Observaciones')->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('revisor.name')->label('Revisor')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nota_revision')->label('Conclusión')->wrap()->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                Tables\Filters\Filter::make('pendientes')->label('Pendientes de conteo')->query(fn ($query) => $query->whereNull('cantidad_contada')),
                Tables\Filters\Filter::make('diferencias')->label('Con diferencias')->query(fn ($query) => $query->whereNotNull('cantidad_contada')->whereColumn('cantidad_contada', '!=', 'stock_sistema')),
                Tables\Filters\Filter::make('sin_revision')->label('Pendientes de revisión')->query(fn ($query) => $query->whereNotNull('cantidad_contada')->whereNull('revisado_at')),
            ])->headerActions([
                Tables\Actions\Action::make('escanear')->label('Escanear QR / barras')->icon('heroicon-o-qr-code')
                    ->visible(fn () => $this->puedeContar())
                    ->form([BarcodeInput::make('codigo')->label('Código de producto, barras o serie')->required()->maxLength(1000)])
                    ->action(function (array $data) {
                        $linea = app(Servicio::class)->buscarCodigo($this->getOwnerRecord(), $data['codigo']);
                        $this->resetTableFiltersForm();
                        $this->resetTableSearch();
                        $this->replaceMountedTableAction('contar', (string) $linea->id, ['codigo_leido' => trim($data['codigo'])]);
                    }),
            ])->actions([
                Tables\Actions\Action::make('contar')->label(fn ($record) => $record->cantidad_contada === null ? 'Contar' : 'Recontar')
                    ->icon('heroicon-o-pencil-square')->visible(fn () => $this->puedeContar())
                    ->modalHeading(fn ($record) => $record->codigo.' · '.$record->nombre)
                    ->fillForm(fn ($record, array $arguments) => [
                        'cantidad_contada' => $record->cantidad_contada, 'version' => $record->version,
                        'observaciones' => $record->observaciones, 'codigo_leido' => $arguments['codigo_leido'] ?? null,
                    ])
                    ->form([
                        Hidden::make('version'),
                        Placeholder::make('referencia')->label('Stock físico de referencia')->content(fn ($record) => $record->stock_sistema.' '.$record->unidad),
                        TextInput::make('cantidad_contada')->label('Cantidad física total')->numeric()->minValue(0)->step('0.000001')->required(),
                        BarcodeInput::make('codigo_leido')->label('QR / barras leído (opcional)')->maxLength(1000),
                        Textarea::make('observaciones')->label('Observaciones de la diferencia')->maxLength(4000),
                        Textarea::make('motivo')->label('Motivo del reconteo')->maxLength(4000)->required(fn ($record) => $record->cantidad_contada !== null),
                    ])->action(function (InventarioConteo $record, array $data) {
                        app(Servicio::class)->contar($record, $data);
                        Notification::make()->title('Conteo registrado')->success()->send();
                        $this->dispatch('inventario-actualizado');
                    }),
                Tables\Actions\Action::make('revisar')->label('Revisar')->icon('heroicon-o-check-badge')
                    ->visible(fn ($record) => auth()->user()->can(Servicio::PROGRAMAR) && $this->getOwnerRecord()->fresh()->estado === 'en_revision' && ! $record->revisado_at)
                    ->form([
                        Placeholder::make('comparacion')->label('Resultado')->content(fn ($record) => 'Referencia: '.$record->stock_sistema.' | Contado: '.$record->cantidad_contada.' | Diferencia: '.$record->diferencia),
                        Placeholder::make('observacion_conteo')->label('Observaciones del conteo')->content(fn ($record) => $record->observaciones ?: 'Sin observaciones'),
                        Placeholder::make('stock_actual')->label('Stock físico actual del sistema')
                            ->content(fn ($record) => \App\Models\Inventario\Existencia::where('articulo_id', $record->articulo_id)->where('almacen_id', $this->getOwnerRecord()->almacen_id)->sum('cantidad_disponible'))
                            ->helperText('Si hubo movimientos desde el inicio, explique su efecto en la conclusión. La diferencia del conteo usa el stock de referencia, no este saldo actual.'),
                        Textarea::make('nota')->label('Conclusión de la revisión')->required()->maxLength(4000),
                    ])->action(function ($record, array $data) {
                        app(Servicio::class)->revisar($record, $data['nota']);
                        Notification::make()->title('Revisión registrada')->success()->send();
                        $this->dispatch('inventario-actualizado');
                    }),
            ])->bulkActions([])->defaultSort('codigo')->paginated([25, 50, 100])->defaultPaginationPageOption(50)->poll('30s')
            ->emptyStateHeading('No hay artículos que mostrar')->emptyStateDescription('Inicie el inventario para preparar los artículos o revise los filtros de búsqueda.');
    }
}
