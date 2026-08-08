<?php

namespace App\Filament\Resources\Compras\OrdenCompraResource\RelationManagers;

use App\Models\Compras\Recepcion;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class RecepcionesRelationManager extends RelationManager
{
    protected static string $relationship = 'recepciones';

    protected static ?string $title = '📥 Recepciones';

    protected static ?string $modelLabel = 'Recepción';

    protected static ?string $pluralModelLabel = 'Recepciones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos de la Recepción')
                    ->icon('heroicon-o-inbox')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Código')
                                    ->required()
                                    ->disabled()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->default(fn() => Recepcion::generarCodigo())
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->columnSpan(1),

                                DatePicker::make('fecha_recepcion')
                                    ->label('Fecha Recepción')
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->columnSpan(1),

                                Select::make('estado')
                                    ->label('Estado')
                                    ->disabled()
                                    ->dehydrated()
                                    ->options([
                                        'pendiente' => '⏳ Pendiente',
                                        'parcial' => '📦 Parcial',
                                        'completada' => '✅ Completada',
                                        'rechazada' => '❌ Rechazada',
                                    ])
                                    ->default('pendiente')
                                    ->required()
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-tag')
                                    ->columnSpan(1),

                                TextInput::make('guia_remision')
                                    ->label('Guía de Remisión')
                                    ->maxLength(50)
                                    ->placeholder('Número de guía')
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('transportista')
                                    ->label('Transportista')
                                    ->maxLength(100)
                                    ->placeholder('Nombre del transportista')
                                    ->prefixIcon('heroicon-o-truck')
                                    ->columnSpan(1),

                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(2)
                                    ->placeholder('Observaciones de la recepción...')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                DatePicker::make('fecha_recepcion')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match($state) {
                        'pendiente' => '⏳ Pendiente',
                        'parcial' => '📦 Parcial',
                        'completada' => '✅ Completada',
                        'rechazada' => '❌ Rechazada',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendiente',
                        'info' => 'parcial',
                        'success' => 'completada',
                        'danger' => 'rechazada',
                    ]),

                TextColumn::make('transportista')
                    ->label('Transportista')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'parcial' => 'Parcial',
                        'completada' => 'Completada',
                        'rechazada' => 'Rechazada',
                    ])
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Recepción')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Crear Recepción')
                    ->modalWidth('5xl')
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $data['orden_compra_id'] = $livewire->getOwnerRecord()->id;
                        $data['proveedor_id'] = $livewire->getOwnerRecord()->proveedor_id;
                        $data['creado_por'] = Auth::id();
                        $data['empresa_id'] = Auth::user()?->empresa_id ?? 1;
                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Recepción creada exitosamente')
                            ->body('La recepción ' . $record->codigo . ' ha sido creada.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),

                    Tables\Actions\Action::make('completar')
                        ->label('Completar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->estado = 'completada';
                            $record->save();
                            Notification::make()
                                ->title('Recepción completada')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'parcial'),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }
}