<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Compras\Proveedor;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class ProveedoresRelationManager extends RelationManager
{
    protected static string $relationship = 'proveedores';

    protected static ?string $title = 'Proveedores';

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Proveedor')
                    ->icon('heroicon-o-users')
                    ->description('Datos de la relación entre el artículo y el proveedor')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('proveedor_id')
                                    ->label('Proveedor')
                                    ->options(fn () => Proveedor::where('activo', true)
                                        ->pluck('nombre', 'id')
                                        ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un proveedor')
                                    ->helperText('Selecciona el proveedor para este artículo')
                                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                        return $rule->where('articulo_id', $get('articulo_id') ?? request()->route('record'));
                                    }),

                                TextInput::make('codigo_proveedor')
                                    ->label('Código del Proveedor')
                                    ->maxLength(100)
                                    ->placeholder('Ej: PROV-001')
                                    ->helperText('Código que el proveedor usa para este artículo'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('costo_compra')
                                    ->label('Costo de Compra')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->helperText('Costo al que se compra este artículo al proveedor'),

                                Toggle::make('es_principal')
                                    ->label('Proveedor Principal')
                                    ->default(false)
                                    ->helperText('Marca este proveedor como el principal para este artículo')
                                    ->live(),
                            ]),

                        // ========== INFORMACIÓN DEL PROVEEDOR ==========
                        Forms\Components\Placeholder::make('proveedor_info')
                            ->label('Información de Contacto')
                            ->content(function ($get) {
                                $proveedorId = $get('proveedor_id');
                                if (!$proveedorId) {
                                    return 'Seleccione un proveedor para ver su información de contacto.';
                                }

                                $proveedor = Proveedor::find($proveedorId);
                                if (!$proveedor) {
                                    return 'Proveedor no encontrado.';
                                }

                                $html = '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">';
                                $html .= '<div class="grid grid-cols-2 gap-2 text-sm">';
                                
                                $html .= '<div><span class="font-medium">Código:</span> ' . $proveedor->codigo . '</div>';
                                $html .= '<div><span class="font-medium">NIT:</span> ' . ($proveedor->nit ?? 'N/A') . '</div>';
                                
                                if ($proveedor->telefono) {
                                    $html .= '<div><span class="font-medium">Teléfono:</span> ' . $proveedor->telefono . '</div>';
                                }
                                
                                if ($proveedor->correo) {
                                    $html .= '<div><span class="font-medium">Correo:</span> ' . $proveedor->correo . '</div>';
                                }
                                
                                if ($proveedor->contacto_nombre) {
                                    $html .= '<div><span class="font-medium">Contacto:</span> ' . $proveedor->contacto_nombre . '</div>';
                                }
                                
                                if ($proveedor->contacto_telefono) {
                                    $html .= '<div><span class="font-medium">Tel. Contacto:</span> ' . $proveedor->contacto_telefono . '</div>';
                                }
                                
                                if ($proveedor->tipo_proveedor) {
                                    $tipos = ['nacional' => 'Nacional', 'internacional' => 'Internacional', 'local' => 'Local'];
                                    $html .= '<div><span class="font-medium">Tipo:</span> ' . ($tipos[$proveedor->tipo_proveedor] ?? $proveedor->tipo_proveedor) . '</div>';
                                }
                                
                                if ($proveedor->calificacion) {
                                    $estrellas = str_repeat('⭐', $proveedor->calificacion);
                                    $html .= '<div><span class="font-medium">Calificación:</span> ' . $estrellas . ' (' . $proveedor->calificacion . '/5)</div>';
                                }
                                
                                if ($proveedor->tiempo_entrega) {
                                    $html .= '<div><span class="font-medium">Tiempo Entrega:</span> ' . $proveedor->tiempo_entrega . ' días</div>';
                                }
                                
                                if ($proveedor->condiciones_pago) {
                                    $html .= '<div><span class="font-medium">Condiciones Pago:</span> ' . $proveedor->condiciones_pago . '</div>';
                                }
                                
                                if ($proveedor->direccion) {
                                    $html .= '<div class="col-span-2"><span class="font-medium">Dirección:</span> ' . $proveedor->direccion . '</div>';
                                }
                                
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('proveedor')
            ->columns([
                TextColumn::make('proveedor.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('proveedor.telefono')
                    ->label('Teléfono')
                    ->toggleable()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('codigo_proveedor')
                    ->label('Código Proveedor')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('costo_compra')
                    ->label('Costo de Compra')
                    ->money('BOB')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('proveedor.tipo_proveedor')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'nacional' => 'Nacional',
                        'internacional' => 'Internacional',
                        'local' => 'Local',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'nacional' => 'info',
                        'internacional' => 'warning',
                        'local' => 'success',
                        default => 'gray',
                    })
                    ->toggleable()
                    ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'tipo_proveedor')),

                TextColumn::make('proveedor.calificacion')
                    ->label('Calificación')
                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) : '-')
                    ->toggleable()
                    ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'calificacion')),

                IconColumn::make('es_principal')
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
                Tables\Filters\SelectFilter::make('proveedor_id')
                    ->label('Proveedor')
                    ->options(fn () => Proveedor::where('activo', true)
                        ->pluck('nombre', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('tipo_proveedor')
                    ->label('Tipo de Proveedor')
                    ->options([
                        'nacional' => 'Nacional',
                        'internacional' => 'Internacional',
                        'local' => 'Local',
                    ])
                    ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'tipo_proveedor')),

                Tables\Filters\TernaryFilter::make('es_principal')
                    ->label('Proveedor Principal')
                    ->boolean()
                    ->trueLabel('Sí')
                    ->falseLabel('No')
                    ->placeholder('Todos'),

                Tables\Filters\Filter::make('costo_compra_mayor_que')
                    ->label('Costo mayor que')
                    ->form([
                        TextInput::make('costo_minimo')
                            ->label('Costo mínimo')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['costo_minimo'],
                            fn (Builder $query, $costo): Builder => $query->where('costo_compra', '>=', $costo)
                        );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Proveedor')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Proveedor al Artículo')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['articulo_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        $proveedor = Proveedor::find($record->proveedor_id);
                        \Filament\Notifications\Notification::make()
                            ->title('Proveedor agregado exitosamente')
                            ->body("El proveedor {$proveedor->nombre} ha sido asignado al artículo")
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
                            $proveedor = Proveedor::find($record->proveedor_id);
                            \Filament\Notifications\Notification::make()
                                ->title('Proveedor actualizado')
                                ->body("La información del proveedor {$proveedor->nombre} ha sido actualizada")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('toggle_principal')
                        ->label('Marcar como Principal')
                        ->icon('heroicon-o-star')
                        ->color(fn ($record) => $record->es_principal ? 'warning' : 'gray')
                        ->action(function ($record) {
                            // Si este proveedor se marca como principal, desmarcar los demás
                            if (!$record->es_principal) {
                                \App\Models\Compras\ArticuloProveedor::where('articulo_id', $record->articulo_id)
                                    ->where('id', '!=', $record->id)
                                    ->update(['es_principal' => false]);
                            }
                            
                            $record->update(['es_principal' => !$record->es_principal]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title($record->es_principal ? 'Proveedor marcado como principal' : 'Proveedor desmarcado como principal')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('ver_proveedor')
                        ->label('Ver Proveedor')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('filament.dashboard.resources.compras.proveedors.edit', $record->proveedor_id))
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            $proveedor = Proveedor::find($record->proveedor_id);
                            \Filament\Notifications\Notification::make()
                                ->title('Proveedor eliminado')
                                ->body("El proveedor {$proveedor->nombre} ha sido desvinculado del artículo")
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
                        ->color('danger'),

                    Tables\Actions\BulkAction::make('set_principal')
                        ->label('Marcar como Principal')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(function ($records) {
                            $articuloId = $records->first()->articulo_id;
                            \App\Models\Compras\ArticuloProveedor::where('articulo_id', $articuloId)
                                ->update(['es_principal' => false]);
                            
                            $records->each->update(['es_principal' => true]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Proveedores marcados como principales')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Marcar como Principal')
                        ->modalSubheading('¿Deseas marcar los proveedores seleccionados como principales?'),

                    Tables\Actions\BulkAction::make('update_costo')
                        ->label('Actualizar Costo')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->form([
                            TextInput::make('nuevo_costo')
                                ->label('Nuevo Costo')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->minValue(0)
                                ->placeholder('0.00'),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['costo_compra' => $data['nuevo_costo']]);
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Costos actualizados')
                                ->body('Se actualizaron los costos de ' . $records->count() . ' proveedores')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Actualizar Costos')
                        ->modalSubheading('¿Deseas actualizar el costo de compra para los proveedores seleccionados?'),
                ]),
            ])
            ->defaultSort('es_principal', 'desc')
            ->searchPlaceholder('Buscar proveedores...')
            ->emptyStateHeading('Sin proveedores asignados')
            ->emptyStateDescription('Asigna proveedores a este artículo')
            ->emptyStateIcon('heroicon-o-users')
            ->poll('60s');
    }
}