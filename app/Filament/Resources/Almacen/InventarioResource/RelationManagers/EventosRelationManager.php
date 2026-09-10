<?php

namespace App\Filament\Resources\Almacen\InventarioResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventosRelationManager extends RelationManager
{
    protected static string $relationship = 'eventos';

    protected static ?string $title = 'Bitácora de auditoría';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->sortable(),
            TextColumn::make('usuario.name')->label('Usuario')->searchable(),
            TextColumn::make('accion')->label('Acción')->badge(),
            TextColumn::make('conteo.codigo')->label('Artículo')->placeholder('Sesión completa')->searchable(),
            TextColumn::make('motivo')->label('Motivo / conclusión')->wrap(),
        ])->recordActions([
            Action::make('detalle')->label('Ver cambios')->modalSubmitAction(false)->modalCancelActionLabel('Cerrar')
                ->schema([
                    TextEntry::make('antes')->label('Antes')->getStateUsing(fn ($record) => json_encode($record->antes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
                    TextEntry::make('despues')->label('Después')->getStateUsing(fn ($record) => json_encode($record->despues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
                ]),
        ])->headerActions([])->toolbarActions([])->defaultSort('id', 'desc');
    }
}
