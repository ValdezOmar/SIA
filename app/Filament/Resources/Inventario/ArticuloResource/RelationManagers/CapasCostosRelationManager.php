<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DatabaseSchema;

class CapasCostosRelationManager extends RelationManager
{
    protected static string $relationship = 'capasCostos';

    protected static ?string $title = 'Capas de costo FIFO';

    protected static ?string $modelLabel = 'Capa FIFO';

    protected static ?string $pluralModelLabel = 'Capas FIFO';

    public static function canViewForRecord($record, $pageClass): bool
    {
        return $record && DatabaseSchema::hasTable('alm_capas_costos') && DatabaseSchema::hasColumn('alm_capas_costos', 'articulo_id');
    }

    private static function monto(float|int|null $monto): string
    {
        return 'Bs '.number_format((float) $monto, 2);
    }

    private static function porcentajeDisponible($record): float
    {
        $original = (float) ($record?->cantidad_original ?? 0);

        return $original > 0 ? round(((float) $record->cantidad_disponible / $original) * 100, 1) : 0;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación de la capa')
                ->description('Una capa representa una entrada valorizada que FIFO consumirá desde la más antigua.')
                ->schema([
                    Grid::make(4)->schema([
                        Placeholder::make('id')->label('Capa FIFO')->content(fn ($record) => '#'.($record?->id ?? '—')),
                        Placeholder::make('almacen.nombre')->label('Almacén')->content(fn ($record) => $record?->almacen?->nombre ?? '—'),
                        Placeholder::make('fecha')->label('Fecha de entrada')->content(fn ($record) => $record?->fecha?->format('d/m/Y H:i:s') ?? '—'),
                        Placeholder::make('kardex_id')->label('Kardex de origen')->content(fn ($record) => $record?->kardex_id ? '#'.$record->kardex_id : 'Sin referencia'),
                    ]),
                ]),
            Section::make('Disponibilidad y valoración')
                ->schema([
                    Grid::make(4)->schema([
                        Placeholder::make('cantidad_original')->label('Cantidad recibida')->content(fn ($record) => number_format((float) ($record?->cantidad_original ?? 0), 2)),
                        Placeholder::make('cantidad_consumida')->label('Cantidad consumida')->content(fn ($record) => number_format(max(0, (float) ($record?->cantidad_original ?? 0) - (float) ($record?->cantidad_disponible ?? 0)), 2)),
                        Placeholder::make('cantidad_disponible')->label('Saldo disponible')->content(fn ($record) => number_format((float) ($record?->cantidad_disponible ?? 0), 2).' ('.self::porcentajeDisponible($record).'%)'),
                        Placeholder::make('costo_unitario')->label('Costo unitario')->content(fn ($record) => self::monto($record?->costo_unitario)),
                    ]),
                    Grid::make(2)->schema([
                        Placeholder::make('valor_original')->label('Valor inicial')->content(fn ($record) => self::monto((float) ($record?->cantidad_original ?? 0) * (float) ($record?->costo_unitario ?? 0))),
                        Placeholder::make('valor_disponible')->label('Valor pendiente en FIFO')->content(fn ($record) => self::monto((float) ($record?->cantidad_disponible ?? 0) * (float) ($record?->costo_unitario ?? 0))),
                    ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('fecha')->label('Entrada FIFO')->dateTime('d/m/Y H:i')->sortable()->description(fn ($record) => 'Capa #'.$record->id),
                TextColumn::make('almacen.nombre')->label('Almacén')->badge()->color('info')->searchable()->sortable(),
                TextColumn::make('cantidad_original')->label('Recibido')->numeric(2)->sortable(),
                TextColumn::make('cantidad_consumida')->label('Consumido')->getStateUsing(fn ($record) => max(0, (float) $record->cantidad_original - (float) $record->cantidad_disponible))->numeric(2)->color('warning')->description(fn ($record) => self::porcentajeDisponible($record).'% permanece disponible'),
                TextColumn::make('cantidad_disponible')->label('Pendiente FIFO')->numeric(2)->sortable()->weight('bold')->color(fn ($record) => $record->cantidad_disponible > 0 ? 'success' : 'gray'),
                TextColumn::make('costo_unitario')->label('Costo unitario')->money('BOB')->sortable(),
                TextColumn::make('valor_disponible')->label('Valor pendiente')->getStateUsing(fn ($record) => (float) $record->cantidad_disponible * (float) $record->costo_unitario)->money('BOB')->weight('medium'),
                TextColumn::make('kardex_id')->label('Origen')->formatStateUsing(fn ($state) => $state ? 'Kardex #'.$state : 'Sin referencia')->toggleable(),
                BadgeColumn::make('activo')->label('Estado FIFO')->formatStateUsing(fn ($state) => $state ? 'Disponible' : 'Cerrada')->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('almacen_id')->label('Almacén')->relationship('almacen', 'nombre')->searchable()->preload(),
                Filter::make('disponibles')->label('Solo con saldo')->query(fn ($query) => $query->where('activo', true)->where('cantidad_disponible', '>', 0)),
                Filter::make('agotadas')->label('Capas agotadas')->query(fn ($query) => $query->where(fn ($q) => $q->where('activo', false)->orWhere('cantidad_disponible', '<=', 0))),
            ])
            ->recordActions([ViewAction::make()->slideOver()->modalWidth('5xl')])
            ->defaultSort('fecha')
            ->searchPlaceholder('Buscar capa FIFO...')
            ->emptyStateHeading('No existen capas FIFO para este artículo')
            ->emptyStateDescription('Las capas se generan al confirmar entradas que valorizan inventario.')
            ->emptyStateIcon('heroicon-o-circle-stack')
            ->poll('60s');
    }
}