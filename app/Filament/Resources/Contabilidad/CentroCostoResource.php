<?php

namespace App\Filament\Resources\Contabilidad;

use App\Filament\Resources\Contabilidad\CentroCostoResource\Pages;
use App\Models\Contabilidad\CentroCosto;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CentroCostoResource extends Resource
{
    protected static ?string $model = CentroCosto::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Contabilidad';

    protected static ?string $navigationLabel = 'Centros de Costo';

    protected static ?string $modelLabel = 'Centro de Costo';

    protected static ?string $pluralModelLabel = 'Centros de Costo';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos del Centro de Costo')
                    ->icon('heroicon-o-rectangle-stack')
                    ->description('Información del centro de costo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Código')
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('CC-001')
                                    ->helperText('Código único del centro de costo')
                                    ->prefixIcon('heroicon-o-hashtag'),

                                TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Departamento de Ventas, Producción, etc.')
                                    ->helperText('Nombre del centro de costo')
                                    ->prefixIcon('heroicon-o-document-text'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('area_id')
                                    ->label('Área')
                                    ->relationship('area', 'nombre')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un área')
                                    ->helperText('Área asociada al centro de costo')
                                    ->prefixIcon('heroicon-o-building-office'),

                                Select::make('responsable_id')
                                    ->label('Responsable')
                                    ->relationship('responsable', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un responsable')
                                    ->helperText('Responsable del centro de costo')
                                    ->prefixIcon('heroicon-o-user'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('tipo')
                                    ->label('Tipo')
                                    ->options([
                                        'costo' => '💰 Costo',
                                        'ingreso' => '📈 Ingreso',
                                        'mixto' => '🔄 Mixto',
                                    ])
                                    ->default('costo')
                                    ->required()
                                    ->searchable()
                                    ->helperText('Tipo de centro de costo')
                                    ->prefixIcon('heroicon-o-tag'),

                                Toggle::make('activo')
                                    ->label('Activo')
                                    ->default(true)
                                    ->helperText('Centro de costo disponible para uso'),
                            ]),

                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Descripción del centro de costo...')
                            ->helperText('Información adicional sobre el centro de costo')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable()
                    ->width('120px')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30),

                TextColumn::make('area.nombre')
                    ->label('Área')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'costo' => '💰 Costo',
                        'ingreso' => '📈 Ingreso',
                        'mixto' => '🔄 Mixto',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'costo' => 'danger',
                        'ingreso' => 'success',
                        'mixto' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),

                IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'costo' => 'Costo',
                        'ingreso' => 'Ingreso',
                        'mixto' => 'Mixto',
                    ])
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),

                    Tables\Actions\DeleteAction::make(),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('codigo')
            ->searchPlaceholder('Buscar centro de costo...')
            ->emptyStateHeading('No hay centros de costo registrados')
            ->emptyStateDescription('Crea un centro de costo para comenzar.')
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCentroCostos::route('/'),
            'create' => Pages\CreateCentroCosto::route('/create'),
            'edit' => Pages\EditCentroCosto::route('/{record}/edit'),
        ];
    }
}
