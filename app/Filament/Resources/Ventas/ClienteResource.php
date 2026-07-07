<?php

namespace App\Filament\Resources\Ventas;

use App\Filament\Resources\Ventas\ClienteResource\Pages;
use App\Filament\Resources\Ventas\ClienteResource\RelationManagers\CotizacionesRelationManager;
use App\Models\Ventas\Cliente;
use App\Models\Inventario\ListaPrecio;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Cliente')
                    ->tabs([

                        // ========== TAB 1: INFORMACIÓN GENERAL ==========
                        Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos Básicos')
                                    ->icon('heroicon-o-identification')
                                    ->description('Información principal del cliente')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->disabled()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Generado automáticamente')
                                                    ->helperText('Código único del cliente')
                                                    ->default(fn() => Cliente::generarCodigo())
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                TextInput::make('nombre')
                                                    ->label('Nombre / Razón Social')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Juan Pérez')
                                                    ->helperText('Nombre completo o razón social')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->columnSpan(2),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('razon_social')
                                                    ->label('Razón Social')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Pérez S.A.')
                                                    ->helperText('Razón social (si es empresa)')
                                                    ->prefixIcon('heroicon-o-building-office')
                                                    ->columnSpan(1),

                                                TextInput::make('ci/nit')
                                                    ->label('CI / NIT')
                                                    ->maxLength(50)
                                                    ->placeholder('Ej: 123456789')
                                                    ->helperText('Cédula de identidad o NIT')
                                                    ->prefixIcon('heroicon-o-identification')
                                                    ->columnSpan(1),

                                                Select::make('tipo_cliente')
                                                    ->label('Tipo de Cliente')
                                                    ->options([
                                                        'persona_natural' => '👤 Persona Natural',
                                                        'empresa' => '🏢 Empresa',
                                                        'gobierno' => '🏛️ Gobierno',
                                                        'extranjero' => '🌍 Extranjero',
                                                    ])
                                                    ->default('persona_natural')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Clasificación del cliente')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Select::make('categoria')
                                                    ->label('Categoría')
                                                    ->options([
                                                        'regular' => 'Regular',
                                                        'mayorista' => 'Mayorista',
                                                        'minorista' => 'Minorista',
                                                        'vip' => '⭐ VIP',
                                                        'revendedor' => 'Revendedor',
                                                    ])
                                                    ->default('regular')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Categoría comercial del cliente')
                                                    ->prefixIcon('heroicon-o-star')
                                                    ->columnSpan(1),

                                                Select::make('lista_precio_id')
                                                    ->label('Lista de Precios')
                                                    ->options(
                                                        fn() => ListaPrecio::where('activo', true)
                                                            ->pluck('nombre', 'id')
                                                            ->toArray()
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Sin lista asignada')
                                                    ->helperText('Lista de precios predeterminada')
                                                    ->prefixIcon('heroicon-o-currency-dollar')
                                                    ->columnSpan(2)
                                                    ->default(function () {
                                                        try {
                                                            $primeraLista = ListaPrecio::where('activo', true)->first();
                                                            return $primeraLista?->id;
                                                        } catch (\Exception $e) {
                                                            return null;
                                                        }
                                                    }),
                                            ]),
                                    ]),

                                Section::make('Ubicación')
                                    ->icon('heroicon-o-map-pin')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('celular')
                                                    ->label('Celular')
                                                    ->maxLength(50)
                                                    ->placeholder('Ej: (591) 7-1234567')
                                                    ->helperText('Teléfono móvil')
                                                    ->prefixIcon('heroicon-o-device-phone-mobile')
                                                    ->columnSpan(1),

                                                Select::make('ciudad')
                                                    ->label('Departamento / Ciudad')
                                                    ->options([
                                                        'Beni' => 'Beni',
                                                        'Chuquisaca' => 'Chuquisaca',
                                                        'Cochabamba' => 'Cochabamba',
                                                        'La Paz' => 'La Paz',
                                                        'Oruro' => 'Oruro',
                                                        'Pando' => 'Pando',
                                                        'Potosí' => 'Potosí',
                                                        'Santa Cruz' => 'Santa Cruz',
                                                        'Tarija' => 'Tarija',
                                                    ])
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un departamento')
                                                    ->helperText('Departamento del cliente')
                                                    ->prefixIcon('heroicon-o-map-pin')
                                                    ->columnSpan(1),

                                                TextInput::make('zona')
                                                    ->label('Zona')
                                                    ->maxLength(100)
                                                    ->placeholder('Ej: Equipetrol')
                                                    ->helperText('Zona o barrio')
                                                    ->prefixIcon('heroicon-o-building-library')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Estado')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Toggle::make('activo')
                                                    ->label('Cliente Activo')
                                                    ->default(true)
                                                    ->helperText('Cliente disponible para transacciones')
                                                    ->columnSpan(1),

                                                Toggle::make('bloqueado')
                                                    ->label('Bloqueado')
                                                    ->default(false)
                                                    ->helperText('Bloquea al cliente para transacciones')
                                                    ->live()
                                                    ->columnSpan(1),

                                                TextInput::make('motivo_bloqueo')
                                                    ->label('Motivo de Bloqueo')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Deuda pendiente')
                                                    ->helperText('Razón del bloqueo')
                                                    ->prefixIcon('heroicon-o-exclamation-triangle')
                                                    ->visible(fn(Forms\Get $get) => $get('bloqueado'))
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 2: CONTACTO ==========
                        Tabs\Tab::make('Contacto')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('Datos de Contacto')
                                    ->icon('heroicon-o-phone')
                                    ->description('Información de contacto del cliente')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('telefono')
                                                    ->label('Teléfono')
                                                    ->maxLength(50)
                                                    ->placeholder('Ej: (591) 2-1234567')
                                                    ->helperText('Teléfono fijo')
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->columnSpan(1),
                                                    
                                                TextInput::make('correo')
                                                    ->label('Correo Electrónico')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: cliente@empresa.com')
                                                    ->helperText('Correo electrónico principal')
                                                    ->prefixIcon('heroicon-o-envelope')
                                                    ->columnSpan(1),
                                            ]),

                                        Textarea::make('direccion')
                                            ->label('Dirección')
                                            ->rows(3)
                                            ->placeholder('Ej: Av. Principal #123, Zona Central')
                                            ->helperText('Dirección física del cliente')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Contacto de Referencia')
                                    ->icon('heroicon-o-user-plus')
                                    ->description('Persona de contacto adicional')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('contacto_nombre')
                                                    ->label('Nombre de Contacto')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: María López')
                                                    ->helperText('Nombre de la persona de contacto')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->columnSpan(1),

                                                TextInput::make('contacto_telefono')
                                                    ->label('Teléfono de Contacto')
                                                    ->maxLength(50)
                                                    ->placeholder('Ej: (591) 7-1234567')
                                                    ->helperText('Teléfono de la persona de contacto')
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->columnSpan(1),

                                                TextInput::make('contacto_correo')
                                                    ->label('Correo de Contacto')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: contacto@empresa.com')
                                                    ->helperText('Correo de la persona de contacto')
                                                    ->prefixIcon('heroicon-o-envelope')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 3: COMERCIAL ==========
                        Tabs\Tab::make('Comercial')
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                Section::make('Condiciones Comerciales')
                                    ->icon('heroicon-o-currency-dollar')
                                    ->description('Condiciones de pago y descuentos')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('condicion_pago')
                                                    ->label('Condición de Pago')
                                                    ->maxLength(100)
                                                    ->placeholder('Ej: Crédito 30 días')
                                                    ->helperText('Condiciones de pago acordadas')
                                                    ->prefixIcon('heroicon-o-credit-card')
                                                    ->columnSpan(1),

                                                TextInput::make('descuento_general')
                                                    ->label('Descuento General')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->step(0.01)
                                                    ->default(0)
                                                    ->placeholder('0.00')
                                                    ->helperText('Descuento general aplicable')
                                                    ->prefixIcon('heroicon-o-percent-badge')
                                                    ->columnSpan(1),

                                                TextInput::make('descuento_especial')
                                                    ->label('Descuento Especial')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->step(0.01)
                                                    ->default(0)
                                                    ->placeholder('0.00')
                                                    ->helperText('Descuento especial negociado')
                                                    ->prefixIcon('heroicon-o-gift')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Información Comercial')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Placeholder::make('comercial_info')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return 'La información comercial se mostrará después de guardar el cliente.';
                                                }

                                                return new HtmlString(
                                                    '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                        <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg border border-primary-200 dark:border-primary-800">
                                                            <p class="text-sm text-primary-600 dark:text-primary-400 font-medium">Total Compras</p>
                                                            <p class="text-2xl font-bold text-primary-900 dark:text-primary-100">$0.00</p>
                                                            <p class="text-xs text-primary-500 dark:text-primary-400 mt-1">Histórico de compras</p>
                                                        </div>
                                                        <div class="bg-success-50 dark:bg-success-900/20 p-4 rounded-lg border border-success-200 dark:border-success-800">
                                                            <p class="text-sm text-success-600 dark:text-success-400 font-medium">Compras Realizadas</p>
                                                            <p class="text-2xl font-bold text-success-900 dark:text-success-100">0</p>
                                                            <p class="text-xs text-success-500 dark:text-success-400 mt-1">Número de transacciones</p>
                                                        </div>
                                                        <div class="bg-warning-50 dark:bg-warning-900/20 p-4 rounded-lg border border-warning-200 dark:border-warning-800">
                                                            <p class="text-sm text-warning-600 dark:text-warning-400 font-medium">Saldo Pendiente</p>
                                                            <p class="text-2xl font-bold text-warning-900 dark:text-warning-100">$0.00</p>
                                                            <p class="text-xs text-warning-500 dark:text-warning-400 mt-1">Facturas pendientes de pago</p>
                                                        </div>
                                                    </div>'
                                                );
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 4: AUDITORÍA ==========
                        Tabs\Tab::make('Auditoría')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Información de Auditoría')
                                    ->icon('heroicon-o-clock')
                                    ->description('Registro de creación y modificación')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('creado_por')
                                                    ->label('Creado por')
                                                    ->content(fn($record) => $record?->creador?->name ?? 'N/A')
                                                    ->columnSpan(1),

                                                Placeholder::make('created_at')
                                                    ->label('Fecha de creación')
                                                    ->content(fn($record) => $record?->created_at?->format('d/m/Y H:i') ?? 'N/A')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('updated_at')
                                                    ->label('Última modificación')
                                                    ->content(fn($record) => $record?->updated_at?->format('d/m/Y H:i') ?? 'N/A')
                                                    ->columnSpan(1),

                                                Placeholder::make('deleted_at')
                                                    ->label('Fecha de eliminación')
                                                    ->content(fn($record) => $record?->deleted_at?->format('d/m/Y H:i') ?? 'No eliminado')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->columnSpanFull(),
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
                    ->copyMessage('Código copiado')
                    ->toggleable()
                    ->width('120px'),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->weight('medium'),

                TextColumn::make('razon_social')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('tipo_cliente')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'persona_natural' => '👤 Natural',
                        'empresa' => '🏢 Empresa',
                        'gobierno' => '🏛️ Gobierno',
                        'extranjero' => '🌍 Extranjero',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'persona_natural',
                        'info' => 'empresa',
                        'warning' => 'gobierno',
                        'success' => 'extranjero',
                    ])
                    ->toggleable(),

                BadgeColumn::make('categoria')
                    ->label('Categoría')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'regular' => 'Regular',
                        'mayorista' => 'Mayorista',
                        'minorista' => 'Minorista',
                        'vip' => '⭐ VIP',
                        'revendedor' => 'Revendedor',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'regular',
                        'info' => 'mayorista',
                        'success' => 'minorista',
                        'warning' => 'vip',
                        'primary' => 'revendedor',
                    ])
                    ->toggleable(),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-')
                    ->icon('heroicon-o-phone'),

                TextColumn::make('correo')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-')
                    ->icon('heroicon-o-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->getStateUsing(fn($record) => $record->estado_label)
                    ->colors([
                        'success' => 'Activo',
                        'gray' => 'Inactivo',
                        'danger' => 'Bloqueado',
                    ])
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo_cliente')
                    ->label('Tipo')
                    ->options([
                        'persona_natural' => 'Persona Natural',
                        'empresa' => 'Empresa',
                        'gobierno' => 'Gobierno',
                        'extranjero' => 'Extranjero',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('categoria')
                    ->label('Categoría')
                    ->options([
                        'regular' => 'Regular',
                        'mayorista' => 'Mayorista',
                        'minorista' => 'Minorista',
                        'vip' => 'VIP',
                        'revendedor' => 'Revendedor',
                    ])
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),

                TernaryFilter::make('bloqueado')
                    ->label('Bloqueado')
                    ->boolean()
                    ->trueLabel('Bloqueados')
                    ->falseLabel('No bloqueados')
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    // Tables\Actions\Action::make('duplicate')
                    //     ->label('Duplicar')
                    //     ->icon('heroicon-o-document-duplicate')
                    //     ->color('info')
                    //     ->action(function ($record) {
                    //         $newRecord = $record->replicate();
                    //         $newRecord->codigo = Cliente::generarCodigo();
                    //         $newRecord->created_at = now();
                    //         $newRecord->updated_at = now();
                    //         $newRecord->save();

                    //         Notification::make()
                    //             ->title('Cliente duplicado exitosamente')
                    //             ->success()
                    //             ->send();
                    //     }),

                    // Tables\Actions\Action::make('toggle_active')
                    //     ->label('Activar/Desactivar')
                    //     ->icon('heroicon-o-power')
                    //     ->color(fn($record) => $record->activo ? 'warning' : 'success')
                    //     ->action(function ($record) {
                    //         $record->update(['activo' => !$record->activo]);
                    //         Notification::make()
                    //             ->title($record->activo ? 'Cliente activado' : 'Cliente desactivado')
                    //             ->success()
                    //             ->send();
                    //     }),

                    // Tables\Actions\Action::make('toggle_blocked')
                    //     ->label('Bloquear/Desbloquear')
                    //     ->icon('heroicon-o-lock-closed')
                    //     ->color(fn($record) => $record->bloqueado ? 'success' : 'danger')
                    //     ->action(function ($record) {
                    //         $record->update(['bloqueado' => !$record->bloqueado]);
                    //         Notification::make()
                    //             ->title($record->bloqueado ? 'Cliente bloqueado' : 'Cliente desbloqueado')
                    //             ->success()
                    //             ->send();
                    //     }),

                    //Tables\Actions\DeleteAction::make(),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('toggle_active_bulk')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->action(fn($records) => $records->each->update(['activo' => !$records->first()->activo]))
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar estado de clientes'),

                    Tables\Actions\BulkAction::make('toggle_blocked_bulk')
                        ->label('Bloquear/Desbloquear')
                        ->icon('heroicon-o-lock-closed')
                        ->action(fn($records) => $records->each->update(['bloqueado' => !$records->first()->bloqueado]))
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar bloqueo de clientes'),
                ]),
            ])
            ->defaultSort('nombre')
            ->searchPlaceholder('Buscar cliente por nombre, código, NIT, correo...')
            ->emptyStateHeading('No hay clientes registrados')
            ->emptyStateDescription('Crea tu primer cliente para comenzar a gestionar tus ventas.')
            ->emptyStateIcon('heroicon-o-users')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            CotizacionesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}