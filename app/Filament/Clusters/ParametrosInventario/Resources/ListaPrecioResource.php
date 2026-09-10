<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\Pages\ListListaPrecios;
use App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\Pages\CreateListaPrecio;
use App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\Pages\EditListaPrecio;
use App\Filament\Clusters\ParametrosInventario;
use App\Filament\Concerns\ScopesEmpresa;
use App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\Pages;
use App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\RelationManagers\PreciosRelationManager;
use App\Models\Inventario\ListaPrecio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListaPrecioResource extends Resource
{
    use ScopesEmpresa;
    protected static ?string $model = ListaPrecio::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = ParametrosInventario::class;

    protected static ?string $navigationLabel = 'Listas de precios';

    protected static ?string $modelLabel = 'Lista de precios';

    protected static ?string $pluralModelLabel = 'Listas de precios';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            self::empresaField(),
            Section::make('Identificación de la lista')
                ->description('Use una lista por política comercial, tipo de cliente o moneda.')
                ->icon('heroicon-o-identification')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('codigo')->disabled()->dehydrated(false)->label('Código')->required(false)->maxLength(50)->unique(ignoreRecord: true)
                            ->placeholder('Se genera automáticamente')->helperText('Identificador único generado al guardar.'),
                        TextInput::make('nombre')->label('Nombre de la lista')->required()->maxLength(100)
                            ->placeholder('Ej.: Precio general')->helperText('Nombre visible al seleccionar precios.'),
                    ]),
                ]),
            Section::make('Disponibilidad y moneda')
                ->description('Active la lista sólo cuando esté lista para ser utilizada.')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('moneda')->label('Moneda')->options([
                            'BOB' => 'Bolivianos (BOB)',
                            'USD' => 'Dólares estadounidenses (USD)',
                        ])->default('BOB')->required()->helperText('Todos los precios de esta lista usarán esta moneda.'),
                        Toggle::make('activo')->label('Lista activa')->default(true)
                            ->helperText('Desactive la lista si ya no debe poder seleccionarse.'),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('codigo')->label('Código')->searchable()->sortable()->copyable()->copyMessage('Código copiado'),
            TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
            TextColumn::make('moneda')->label('Moneda')->badge()->color(fn ($state) => $state === 'BOB' ? 'success' : 'warning')
                ->formatStateUsing(fn ($state) => $state === 'BOB' ? 'Bolivianos (BOB)' : 'Dólares (USD)'),
            IconColumn::make('activo')->label('Disponible')->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')->falseColor('danger'),
            TextColumn::make('precios_count')->label('Precios asignados')->counts('precios')->badge()->color('info')->toggleable(),
            TextColumn::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('moneda')->label('Moneda')->options(['BOB' => 'Bolivianos', 'USD' => 'Dólares']),
            TernaryFilter::make('activo')->label('Disponibilidad')->boolean()->trueLabel('Activas')->falseLabel('Inactivas')->placeholder('Todas'),
        ])->recordActions([
            ActionGroup::make([
                EditAction::make()->label('Editar')->slideOver()->modalWidth('4xl'),
                Action::make('duplicate')->label('Duplicar')->icon('heroicon-o-document-duplicate')->color('info')
                    ->action(function ($record): void {
                        $newRecord = $record->replicate();
                        $newRecord->codigo = $record->codigo.'-COPY-'.time();
                        $newRecord->save();
                    }),
                DeleteAction::make(),
            ])->tooltip('Acciones')->icon('heroicon-o-ellipsis-vertical'),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                BulkAction::make('toggle_active')->label('Cambiar disponibilidad')->icon('heroicon-o-power')
                    ->action(fn ($records) => $records->each->update(['activo' => ! $records->first()->activo]))->requiresConfirmation(),
            ]),
        ])->defaultSort('nombre')->searchPlaceholder('Buscar lista de precios...')
            ->emptyStateHeading('No hay listas de precios')
            ->emptyStateDescription('Cree una lista y luego asigne los precios de los artículos.')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }

    public static function getRelations(): array
    {
        return [PreciosRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListListaPrecios::route('/'),
            'create' => CreateListaPrecio::route('/create'),
            'edit' => EditListaPrecio::route('/{record}/edit'),
        ];
    }
}
