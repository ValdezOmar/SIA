<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use Filament\Schemas\Schema;
use App\Models\Inventario\CodigoBarras;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\CreateAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación y uso del código')
                   // ->icon('heroicon-o-barcode')
                    ->description('Registre cada identificador que puede leerse en etiquetas, empaques o puntos de venta. El código principal se usará como referencia predeterminada.')
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                TextInput::make('codigo_barras')
                                    ->label('Código de Barras')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: 7701234567890')
                                    ->helperText('Código único del artículo; puede capturarse con lector.')
                                    ->autofocus()
                                    ->live(onBlur: true)
                                    ->suffixIcon('heroicon-o-qr-code')
                                    ->columnSpan(4),

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
                                    ->helperText('Seleccione el estándar impreso en la etiqueta.')
                                    ->default('EAN-13')
                                    ->columnSpan(2),
                            ]),

                        Grid::make(6)
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
                                    })
                                    ->columnSpan(2),

                                Placeholder::make('info')
                                    ->label('Verificación del identificador')
                                    ->content(function ($get) {
                                        $codigo = trim((string) ($get('codigo_barras') ?? ''));
                                        $tipo = $get('tipo');

                                        if ($codigo === '') {
                                            return new HtmlString('<div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">Ingrese o escanee el código. El sistema verificará la longitud según el tipo seleccionado.</div>');
                                        }

                                        $longitud = strlen($codigo);
                                        $sugerido = match ($longitud) { 13 => 'EAN-13', 12 => 'UPC-A', 8 => 'EAN-8', 6 => 'UPC-E', default => 'Código de longitud libre' };
                                        $valido = ! $tipo || CodigoBarras::validarFormato($codigo, $tipo);
                                        $estado = $valido ? 'Formato compatible' : 'Revise la longitud para '.e($tipo);
                                        $color = $valido ? 'text-success-700 dark:text-success-300' : 'text-danger-700 dark:text-danger-300';

                                        return new HtmlString('<div class="rounded-lg border border-primary-200 bg-primary-50 p-3 text-sm dark:border-primary-800 dark:bg-primary-950/30"><div class="grid gap-2 sm:grid-cols-3"><div><span class="font-medium">Longitud</span><br>'.$longitud.' caracteres</div><div><span class="font-medium">Formato detectado</span><br>'.e($sugerido).'</div><div class="'.$color.'"><span class="font-medium">Estado</span><br>'.$estado.'</div></div></div>');
                                    })
                                    ->columnSpanFull(),

                                TextInput::make('descripcion')
                                    ->label('Uso o presentación')
                                    ->maxLength(255)
                                    ->placeholder('Ej.: etiqueta de unidad, caja de 12 unidades o código del proveedor')
                                    ->helperText('Aclara dónde se encuentra o cuándo debe usarse este identificador.')
                                    ->prefixIcon('heroicon-o-tag')
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
                    ->color(fn ($state) => match ($state) {
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

                TextColumn::make('descripcion')
                    ->label('Uso / presentación')
                    ->placeholder('Sin descripción')
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->descripcion)
                    ->toggleable(),

                TextColumn::make('longitud')
                    ->label('Dígitos')
                    ->getStateUsing(fn ($record) => $record->longitud)
                    ->alignCenter()
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
                SelectFilter::make('tipo')
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

                TernaryFilter::make('principal')
                    ->label('Código Principal')
                    ->boolean()
                    ->trueLabel('Sí')
                    ->falseLabel('No')
                    ->placeholder('Todos'),

                Filter::make('longitud')
                    ->label('Longitud del código')
                    ->schema([
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
                CreateAction::make()
                    ->label('Agregar código')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar identificador del artículo')
                    ->modalDescription('Registre el código tal como aparece en la etiqueta. Marque Principal cuando sea el identificador preferido para búsqueda y lectura.')
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
                            ->body('El código '.$record->codigo_barras.' ha sido agregado exitosamente.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl')
                        ->after(function ($record) {
                            Notification::make()
                                ->title('Código actualizado')
                                ->body('El código de barras ha sido actualizado.')
                                ->success()
                                ->send();
                        }),

                    Action::make('set_principal')
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
                                ->body('El código '.$record->codigo_barras.' es ahora el principal.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => ! $record->principal),

                    DeleteAction::make()
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
                                        ->body('El código '.$nuevoPrincipal->codigo_barras.' es ahora el principal.')
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
            ->defaultSort('principal', 'desc')
            ->searchPlaceholder('Buscar código de barras...')
            ->emptyStateHeading('Sin códigos de barras')
            ->emptyStateDescription('Agrega códigos de barras para este artículo')
            // ->emptyStateIcon('heroicon-o-barcode')
            ->poll('60s');
    }
}
