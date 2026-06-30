<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CodigosBarrasRelationManager extends RelationManager
{
    protected static string $relationship = 'codigosBarras';

    protected static ?string $title = 'Códigos de Barras';

    protected static ?string $modelLabel = 'Código de Barras';

    protected static ?string $pluralModelLabel = 'Códigos de Barras';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Gestión de Código de Barras')
                   // ->icon('heroicon-o-barcode')
                    ->description('Administra los códigos de barras asociados a este artículo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('codigo_barras')
                                    ->label('Código de Barras')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: 7701234567890')
                                    ->helperText('Código de barras único del artículo')
                                    ->columnSpan(1),

                                Select::make('tipo')
                                    ->label('Tipo de Código')
                                    ->options([
                                        'EAN-13' => 'EAN-13',
                                        'EAN-8' => 'EAN-8',
                                        'UPC-A' => 'UPC-A',
                                        'UPC-E' => 'UPC-E',
                                        'CODE-128' => 'CODE-128',
                                        'CODE-39' => 'CODE-39',
                                        'QR' => 'QR',
                                        'PDF417' => 'PDF417',
                                        'DataMatrix' => 'DataMatrix',
                                        'Interno' => 'Interno',
                                        'Otro' => 'Otro',
                                    ])
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un tipo')
                                    ->helperText('Tipo de código de barras')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('principal')
                                    ->label('Es el código principal')
                                    ->default(false)
                                    ->helperText('Marca este código como el principal del artículo')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                        // Si se marca como principal, desmarcar los demás
                                        if ($state) {
                                            // Obtener el artículo actual
                                            $articulo = $livewire->getOwnerRecord();
                                            if ($articulo) {
                                                // Obtener el ID del registro actual desde el estado del formulario
                                                $recordId = $livewire->getMountedTableActionRecord();
                                                if ($recordId) {
                                                    $articulo->codigosBarras()
                                                        ->where('id', '!=', $recordId)
                                                        ->update(['principal' => false]);
                                                } else {
                                                    // Si no hay recordId (creación), desmarcar todos
                                                    $articulo->codigosBarras()->update(['principal' => false]);
                                                }
                                            }
                                        }
                                    }),

                                Forms\Components\Placeholder::make('info')
                                    ->label('')
                                    ->content(function ($get) {
                                        $codigo = $get('codigo_barras');
                                        $tipo = $get('tipo');
                                        
                                        if (!$codigo) {
                                            return 'Ingrese un código de barras para verificar su formato.';
                                        }
                                        
                                        $longitud = strlen($codigo);
                                        $tipoSugerido = '';
                                        
                                        if ($longitud === 13) {
                                            $tipoSugerido = ' (EAN-13)';
                                        } elseif ($longitud === 8) {
                                            $tipoSugerido = ' (EAN-8)';
                                        } elseif ($longitud === 12) {
                                            $tipoSugerido = ' (UPC-A)';
                                        }
                                        
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm text-gray-500">
                                                <span class="font-medium">Longitud:</span> ' . $longitud . ' dígitos' . $tipoSugerido . '
                                            </div>'
                                        );
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo_barras')
            ->columns([
                TextColumn::make('codigo_barras')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->toggleable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'EAN-13' => 'primary',
                        'EAN-8' => 'info',
                        'UPC-A' => 'success',
                        'UPC-E' => 'success',
                        'CODE-128' => 'warning',
                        'CODE-39' => 'warning',
                        'QR' => 'danger',
                        'PDF417' => 'danger',
                        'DataMatrix' => 'danger',
                        'Interno' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),

                IconColumn::make('principal')
                    ->label('Principal')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(),

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
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo de Código')
                    ->options([
                        'EAN-13' => 'EAN-13',
                        'EAN-8' => 'EAN-8',
                        'UPC-A' => 'UPC-A',
                        'UPC-E' => 'UPC-E',
                        'CODE-128' => 'CODE-128',
                        'CODE-39' => 'CODE-39',
                        'QR' => 'QR',
                        'PDF417' => 'PDF417',
                        'DataMatrix' => 'DataMatrix',
                        'Interno' => 'Interno',
                        'Otro' => 'Otro',
                    ])
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('principal')
                    ->label('Código Principal')
                    ->boolean()
                    ->trueLabel('Sí')
                    ->falseLabel('No')
                    ->placeholder('Todos'),

                Tables\Filters\Filter::make('longitud')
                    ->label('Longitud del código')
                    ->form([
                        TextInput::make('longitud')
                            ->label('Longitud')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->placeholder('Ej: 13'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['longitud'],
                            fn ($query, $longitud) => $query->whereRaw('LENGTH(codigo_barras) = ?', [$longitud])
                        );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuevo Código de Barras')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Código de Barras')
                    ->modalWidth('4xl')
                    ->beforeFormFilled(function ($livewire) {
                        // Si el artículo ya tiene un código principal, sugerir no principal
                        $record = $livewire->getOwnerRecord();
                        if ($record && $record->codigosBarras()->where('principal', true)->exists()) {
                            return ['principal' => false];
                        }
                        return [];
                    })
                    ->after(function ($record) {
                        // Si es el primer código, marcarlo como principal automáticamente
                        $articulo = $this->getOwnerRecord();
                        if ($articulo && $articulo->codigosBarras()->count() === 1) {
                            $record->update(['principal' => true]);
                            
                            Notification::make()
                                ->title('Código marcado como principal')
                                ->body('Por ser el primer código, se ha marcado automáticamente como principal.')
                                ->info()
                                ->send();
                        }
                        
                        Notification::make()
                            ->title('Código de barras agregado')
                            ->body('El código ' . $record->codigo_barras . ' ha sido agregado exitosamente.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl')
                        ->after(function ($record) {
                            Notification::make()
                                ->title('Código actualizado')
                                ->body('El código de barras ha sido actualizado.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('set_principal')
                        ->label('Marcar como Principal')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(function ($record) {
                            // Desmarcar todos los códigos del artículo
                            $articulo = $this->getOwnerRecord();
                            if ($articulo) {
                                $articulo->codigosBarras()->update(['principal' => false]);
                            }
                            // Marcar el seleccionado
                            $record->update(['principal' => true]);
                            
                            Notification::make()
                                ->title('Código marcado como principal')
                                ->body('El código ' . $record->codigo_barras . ' es ahora el principal.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => !$record->principal),

                    Tables\Actions\Action::make('unset_principal')
                        ->label('Quitar como Principal')
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->action(function ($record) {
                            $record->update(['principal' => false]);
                            
                            Notification::make()
                                ->title('Código desmarcado como principal')
                                ->body('El código ya no es el principal.')
                                ->info()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->principal && $this->getOwnerRecord()->codigosBarras()->count() > 1),

                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            // Si es el código principal y hay otros códigos, mostrar advertencia
                            if ($record->principal && $this->getOwnerRecord()->codigosBarras()->count() > 1) {
                                Notification::make()
                                    ->title('Advertencia')
                                    ->body('Este es el código principal. Al eliminarlo, se asignará otro código como principal automáticamente.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->after(function ($record) {
                            // Si se eliminó el principal, asignar otro como principal
                            $articulo = $this->getOwnerRecord();
                            if ($articulo && $record->principal) {
                                $nuevoPrincipal = $articulo->codigosBarras()->first();
                                if ($nuevoPrincipal) {
                                    $nuevoPrincipal->update(['principal' => true]);
                                    
                                    Notification::make()
                                        ->title('Nuevo código principal asignado')
                                        ->body('El código ' . $nuevoPrincipal->codigo_barras . ' es ahora el principal.')
                                        ->info()
                                        ->send();
                                }
                            }
                            
                            Notification::make()
                                ->title('Código eliminado')
                                ->body('El código de barras ha sido eliminado.')
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
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->after(function ($records) {
                            // Asignar nuevo principal si se eliminó el actual
                            $articulo = $this->getOwnerRecord();
                            if ($articulo && !$articulo->codigosBarras()->where('principal', true)->exists()) {
                                $nuevoPrincipal = $articulo->codigosBarras()->first();
                                if ($nuevoPrincipal) {
                                    $nuevoPrincipal->update(['principal' => true]);
                                }
                            }
                        }),

                    Tables\Actions\BulkAction::make('set_principal_bulk')
                        ->label('Marcar como Principal')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Marcar códigos como principal')
                        ->modalSubheading('Solo el primer código seleccionado será marcado como principal')
                        ->action(function ($records) {
                            // Desmarcar todos los códigos del artículo
                            $articulo = $this->getOwnerRecord();
                            if ($articulo) {
                                $articulo->codigosBarras()->update(['principal' => false]);
                            }
                            
                            // Marcar el primer seleccionado
                            $primerRegistro = $records->first();
                            if ($primerRegistro) {
                                $primerRegistro->update(['principal' => true]);
                                
                                Notification::make()
                                    ->title('Código marcado como principal')
                                    ->body('El código ' . $primerRegistro->codigo_barras . ' es ahora el principal.')
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('principal', 'desc')
            ->searchPlaceholder('Buscar código de barras...')
            ->emptyStateHeading('Sin códigos de barras')
            ->emptyStateDescription('Agrega códigos de barras para este artículo')
            //->emptyStateIcon('heroicon-o-barcode')
            ->poll('60s');
    }
}