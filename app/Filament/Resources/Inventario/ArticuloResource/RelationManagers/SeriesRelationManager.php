<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\Almacen;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;

class SeriesRelationManager extends RelationManager
{
    protected static string $relationship = 'series';

    protected static ?string $title = 'Series / Números de Serie';

    protected static ?string $modelLabel = 'Serie';

    protected static ?string $pluralModelLabel = 'Series';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Serie')
                    ->icon('heroicon-o-identification')
                    ->description('Gestiona los números de serie de este artículo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('numero_serie')
                                    ->label('Número de Serie')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: SN-2024-001')
                                    ->helperText('Número de serie único del artículo')
                                    ->columnSpan(1),

                                TextInput::make('codigo_qr')
                                    ->label('Código QR')
                                    ->maxLength(255)
                                    ->placeholder('Ej: QR-001')
                                    ->helperText('Código QR asociado (opcional)')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('imei')
                                    ->label('IMEI')
                                    ->maxLength(255)
                                    ->placeholder('Ej: 123456789012345')
                                    ->helperText('IMEI del dispositivo (opcional)')
                                    ->columnSpan(1),

                                TextInput::make('mac_address')
                                    ->label('MAC Address')
                                    ->maxLength(255)
                                    ->placeholder('Ej: 00:11:22:33:44:55')
                                    ->helperText('Dirección MAC (opcional)')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('almacen_id')
                                    ->label('Almacén')
                                    ->options(fn () => Almacen::where('activo', true)
                                        ->pluck('nombre', 'id')
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un almacén')
                                    ->helperText('Almacén donde se encuentra la serie')
                                    ->columnSpan(1),

                                Select::make('estado')
                                    ->label('Estado')
                                    ->options([
                                        'disponible' => 'Disponible',
                                        'reservado' => 'Reservado',
                                        'vendido' => 'Vendido',
                                        'baja' => 'Baja',
                                        'garantia' => 'En Garantía',
                                        'reparacion' => 'En Reparación',
                                    ])
                                    ->default('disponible')
                                    ->required()
                                    ->searchable()
                                    ->helperText('Estado actual de la serie')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('fecha_garantia')
                                    ->label('Fecha de Garantía')
                                    ->native(false)
                                    ->helperText('Fecha de vencimiento de la garantía')
                                    ->columnSpan(1),

                                DatePicker::make('fecha_venta')
                                    ->label('Fecha de Venta')
                                    ->native(false)
                                    ->helperText('Fecha en que fue vendida la serie')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('fecha_instalacion')
                                    ->label('Fecha de Instalación')
                                    ->native(false)
                                    ->helperText('Fecha de instalación (si aplica)')
                                    ->columnSpan(1),

                                TextInput::make('estado_actual')
                                    ->label('Estado Actual')
                                    ->maxLength(255)
                                    ->placeholder('Ej: Instalado, En uso, Almacenado')
                                    ->helperText('Descripción detallada del estado actual')
                                    ->columnSpan(1),
                            ]),

                        // Información del artículo
                        Forms\Components\Placeholder::make('articulo_info')
                            ->label('')
                            ->content(function ($livewire) {
                                $articulo = $livewire->getOwnerRecord();
                                if (!$articulo) {
                                    return 'No hay artículo asociado.';
                                }
                                
                                return "📦 Artículo: {$articulo->codigo} - {$articulo->descripcion}\n" .
                                       "📋 Unidad: " . ($articulo->unidadMedida->nombre ?? 'N/A');
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_serie')
            ->columns([
                TextColumn::make('numero_serie')
                    ->label('Número de Serie')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Serie copiada')
                    ->toggleable(),

                TextColumn::make('codigo_qr')
                    ->label('Código QR')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('imei')
                    ->label('IMEI')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('mac_address')
                    ->label('MAC Address')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('almacen.nombre')
                    ->label('Almacén')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->placeholder('Sin asignar'),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->searchable()
                    ->sortable()
                    ->colors([
                        'success' => 'disponible',
                        'warning' => 'reservado',
                        'danger' => 'vendido',
                        'gray' => 'baja',
                        'primary' => 'garantia',
                        'info' => 'reparacion',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'disponible',
                        'heroicon-o-clock' => 'reservado',
                        'heroicon-o-shopping-cart' => 'vendido',
                        'heroicon-o-x-circle' => 'baja',
                        'heroicon-o-shield-check' => 'garantia',
                        'heroicon-o-wrench' => 'reparacion',
                    ])
                    ->toggleable(),

                TextColumn::make('fecha_garantia')
                    ->label('Garantía hasta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : 'success')
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('almacen_id')
                    ->label('Almacén')
                    ->options(fn () => Almacen::where('activo', true)
                        ->pluck('nombre', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'disponible' => 'Disponible',
                        'reservado' => 'Reservado',
                        'vendido' => 'Vendido',
                        'baja' => 'Baja',
                        'garantia' => 'En Garantía',
                        'reparacion' => 'En Reparación',
                    ])
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('fecha_garantia')
                    ->label('Garantía')
                    ->form([
                        DatePicker::make('garantia_hasta')
                            ->label('Garantía hasta')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['garantia_hasta'],
                            fn ($query, $fecha) => $query->where('fecha_garantia', '<=', $fecha)
                        );
                    }),

                Tables\Filters\Filter::make('garantia_vencida')
                    ->label('Garantía Vencida')
                    ->query(fn ($query) => $query->where('fecha_garantia', '<', now())->whereNotNull('fecha_garantia')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Serie')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Serie al Artículo')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['articulo_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Serie agregada exitosamente')
                            ->body('La serie ' . $record->numero_serie . ' ha sido registrada.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl')
                        ->mutateFormDataUsing(function (array $data): array {
                            $data['articulo_id'] = $this->getOwnerRecord()->id;
                            return $data;
                        })
                        ->after(function ($record) {
                            Notification::make()
                                ->title('Serie actualizada')
                                ->body('La serie ' . $record->numero_serie . ' ha sido actualizada.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('cambiar_estado')
                        ->label('Cambiar Estado')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Select::make('estado')
                                ->label('Nuevo Estado')
                                ->options([
                                    'disponible' => 'Disponible',
                                    'reservado' => 'Reservado',
                                    'vendido' => 'Vendido',
                                    'baja' => 'Baja',
                                    'garantia' => 'En Garantía',
                                    'reparacion' => 'En Reparación',
                                ])
                                ->required()
                                ->default('disponible'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update(['estado' => $data['estado']]);
                            
                            Notification::make()
                                ->title('Estado actualizado')
                                ->body('La serie ahora está en estado: ' . ucfirst($data['estado']))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            Notification::make()
                                ->title('Serie eliminada')
                                ->body('La serie ' . $record->numero_serie . ' ha sido eliminada.')
                                ->warning()
                                ->send();
                        }),
                ])
                ->tooltip('Acciones')
                ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionadas')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),

                    Tables\Actions\BulkAction::make('cambiar_estado_bulk')
                        ->label('Cambiar Estado')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Select::make('estado')
                                ->label('Estado')
                                ->options([
                                    'disponible' => 'Disponible',
                                    'reservado' => 'Reservado',
                                    'vendido' => 'Vendido',
                                    'baja' => 'Baja',
                                    'garantia' => 'En Garantía',
                                    'reparacion' => 'En Reparación',
                                ])
                                ->required()
                                ->default('disponible'),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['estado' => $data['estado']]);
                            }
                            
                            Notification::make()
                                ->title('Estados actualizados')
                                ->body('Se actualizaron ' . $records->count() . ' series al estado: ' . ucfirst($data['estado']))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar Estado de Series')
                        ->modalSubheading('¿Deseas cambiar el estado de las series seleccionadas?'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar serie...')
            ->emptyStateHeading('Sin series registradas')
            ->emptyStateDescription('Agrega números de serie para este artículo')
            ->emptyStateIcon('heroicon-o-identification')
            ->poll('60s');
    }
}