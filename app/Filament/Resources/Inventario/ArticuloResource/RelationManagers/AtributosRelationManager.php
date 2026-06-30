<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\Atributo;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;

class AtributosRelationManager extends RelationManager
{
    protected static string $relationship = 'atributos';

    protected static ?string $title = 'Atributos';

    protected static ?string $modelLabel = 'Atributo';

    protected static ?string $pluralModelLabel = 'Atributos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Asignación de Atributo')
                    ->icon('heroicon-o-tag')
                    ->description('Asigna un valor a un atributo para este artículo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('atributo_id')
                                    ->label('Atributo')
                                    ->options(fn () => Atributo::where('activo', true)
                                        ->pluck('nombre', 'id')
                                        ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un atributo')
                                    ->helperText('Selecciona el atributo a asignar')
                                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                        return $rule->where('articulo_id', $get('articulo_id') ?? request()->route('record'));
                                    }),

                                TextInput::make('valor')
                                    ->label('Valor')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Rojo, 42, Algodón')
                                    ->helperText('Valor específico del atributo para este artículo'),
                            ]),

                        // Mostrar información del atributo seleccionado
                        Forms\Components\Placeholder::make('atributo_info')
                            ->label('')
                            ->content(function ($get) {
                                $atributoId = $get('atributo_id');
                                if (!$atributoId) {
                                    return 'Seleccione un atributo para ver su información.';
                                }

                                $atributo = Atributo::find($atributoId);
                                if (!$atributo) {
                                    return 'Atributo no encontrado.';
                                }

                                return "📋 {$atributo->nombre}\n" .
                                       "🏷️ Código: {$atributo->codigo}\n" .
                                       ($atributo->descripcion ? "📝 {$atributo->descripcion}" : '');
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('valor')
            ->columns([
                TextColumn::make('atributo.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('atributo.nombre')
                    ->label('Atributo')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('valor')
                    ->label('Valor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('atributo.descripcion')
                    ->label('Descripción')
                    ->toggleable()
                    ->limit(30)
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
                Tables\Filters\SelectFilter::make('atributo_id')
                    ->label('Atributo')
                    ->options(fn () => Atributo::where('activo', true)
                        ->pluck('nombre', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('valor')
                    ->label('Buscar por valor')
                    ->form([
                        TextInput::make('valor')
                            ->label('Valor')
                            ->placeholder('Buscar valor...'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['valor'],
                            fn ($query, $valor) => $query->where('valor', 'like', "%{$valor}%")
                        );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Atributo')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Atributo al Artículo')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['articulo_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        $atributo = Atributo::find($record->atributo_id);
                        \Filament\Notifications\Notification::make()
                            ->title('Atributo agregado exitosamente')
                            ->body("El atributo {$atributo->nombre} ha sido asignado al artículo")
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
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            $atributo = Atributo::find($record->atributo_id);
                            \Filament\Notifications\Notification::make()
                                ->title('Atributo eliminado')
                                ->body("El atributo {$atributo->nombre} ha sido desvinculado del artículo")
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
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar atributos...')
            ->emptyStateHeading('Sin atributos asignados')
            ->emptyStateDescription('Asigna atributos a este artículo')
            ->emptyStateIcon('heroicon-o-tag')
            ->poll('60s');
    }
}