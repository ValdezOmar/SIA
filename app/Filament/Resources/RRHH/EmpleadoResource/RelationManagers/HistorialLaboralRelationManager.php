<?php

namespace App\Filament\Resources\RRHH\EmpleadoResource\RelationManagers;

use App\Models\RRHH\HistorialLaboral;
use App\Models\Sistema\Cargo;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class HistorialLaboralRelationManager extends RelationManager
{
    protected static string $relationship = 'historialLaboral';

    protected static ?string $title = 'Historial laboral';

    protected static ?string $icon = 'heroicon-o-briefcase';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Asignación laboral')
                ->description('Defina la empresa, sucursal y cargo correspondientes a este vínculo.')
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Select::make('empresa_id')
                        ->label('Empresa')
                        ->options(fn (): array => Empresa::query()->where('empresa_activo', true)
                            ->orderBy('nombre_comercial')->get()
                            ->mapWithKeys(fn (Empresa $empresa): array => [
                                $empresa->getKey() => $empresa->nombre_comercial ?: $empresa->razon_social,
                            ])->all())
                        ->searchable()->preload()->live()->required()
                        ->prefixIcon('heroicon-o-building-office')
                        ->helperText('Al cambiar la empresa se actualizarán sucursales, cargos y seguro médico.')
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $set('sucursal_id', null);
                            $set('cargo_id', null);
                            $set('seguro_medico', Empresa::find($state)?->seguro_medico);
                            $set('correo_corporativo', $this->generarCorreoCorporativo($state));
                        }),

                    Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->options(fn (Get $get): array => filled($get('empresa_id'))
                            ? Sucursal::query()->where('empresa_id', $get('empresa_id'))
                                ->orderBy('nombre')->pluck('nombre', 'id')->all()
                            : [])
                        ->searchable()->preload()->required()
                        ->disabled(fn (Get $get): bool => blank($get('empresa_id')))
                        ->prefixIcon('heroicon-o-map-pin'),

                    Select::make('cargo_id')
                        ->label('Área y cargo')
                        ->options(fn (Get $get): array => filled($get('empresa_id'))
                            ? Cargo::query()
                                ->whereHas('area.empresas', fn (Builder $query) => $query->whereKey($get('empresa_id')))
                                ->with('area')->orderBy('nombre')->get()
                                ->mapWithKeys(fn (Cargo $cargo): array => [
                                    $cargo->getKey() => collect([$cargo->area?->nombre, $cargo->nombre])->filter()->implode(' — '),
                                ])->all()
                            : [])
                        ->searchable()->preload()->required()
                        ->disabled(fn (Get $get): bool => blank($get('empresa_id')))
                        ->prefixIcon('heroicon-o-identification'),
                ])->columns(['default' => 1, 'md' => 3]),

            Section::make('Condiciones del contrato')
                ->description('Registre la vigencia y las condiciones económicas del vínculo.')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Select::make('tipo_contrato')
                        ->label('Tipo de contrato')
                        ->options(static::contractOptions())
                        ->default('Contrato indefinido')->native(false)->live()->required(),

                    TextInput::make('tipo_contrato_otro')
                        ->label('Especifique el tipo de contrato')
                        ->placeholder('Ej. Contrato eventual por proyecto')
                        ->visible(fn (Get $get): bool => $get('tipo_contrato') === 'Otro')
                        ->required(fn (Get $get): bool => $get('tipo_contrato') === 'Otro')
                        ->dehydrated(fn (Get $get): bool => $get('tipo_contrato') === 'Otro')->maxLength(255),

                    DatePicker::make('fecha_inicio')
                        ->label('Fecha de ingreso')->default(today())
                        ->displayFormat('d/m/Y')->native(false)->required()->live(),

                    DatePicker::make('fecha_fin')
                        ->label('Fecha de finalización')->displayFormat('d/m/Y')->native(false)
                        ->minDate(fn (Get $get) => $get('fecha_inicio'))
                        ->required(fn (Get $get): bool => $get('tipo_contrato') !== 'Contrato indefinido')
                        ->hidden(fn (Get $get): bool => $get('tipo_contrato') === 'Contrato indefinido')
                        ->dehydrated(fn (Get $get): bool => $get('tipo_contrato') !== 'Contrato indefinido'),

                    TextInput::make('salario')->label('Salario mensual')->prefix('Bs')
                        ->numeric()->minValue(0)->step(0.01)->required(),

                    TextInput::make('seguro_medico')->label('Seguro médico')
                        ->prefixIcon('heroicon-o-heart')->disabled()->dehydrated(),
                ])->columns(['default' => 1, 'md' => 2, 'xl' => 3]),

            Section::make('Contacto corporativo')
                ->description('Estos datos se mostrarán en el directorio interno.')
                ->icon('heroicon-o-at-symbol')
                ->schema([
                    TextInput::make('correo_corporativo')->label('Correo corporativo')
                        ->prefixIcon('heroicon-o-envelope')->email()->required()->maxLength(255),
                    TextInput::make('numero_corporativo')->label('Número corporativo')
                        ->prefixIcon('heroicon-o-phone')->tel()->maxLength(50),
                ])->columns(2),

            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make('Documento de respaldo')
                    ->description('Contrato firmado en PDF, máximo 15 MB.')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        FileUpload::make('documento')->label('Contrato en PDF')
                            ->disk('public')->directory('contratos')
                            ->acceptedFileTypes(['application/pdf'])->maxSize(15360)
                            ->openable()->downloadable()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                $ci = Str::slug($this->getOwnerRecord()->ci ?: 'empleado');

                                return $ci.'-contrato-'.Str::uuid().'.'.Str::lower($file->getClientOriginalExtension());
                            }),
                    ]),
                Section::make('Observaciones')->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Textarea::make('observaciones')->label('Notas internas')
                            ->placeholder('Condiciones especiales, antecedentes o aclaraciones del vínculo.')
                            ->rows(5)->maxLength(2000),
                    ]),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['empresa', 'sucursal', 'cargo.area'])->latest('fecha_inicio')->latest('id'))
            ->recordTitleAttribute('tipo_contrato')
            ->columns([
                IconColumn::make('activo')->label('Vigente')->boolean()
                    ->trueColor('success')->falseColor('gray')
                    ->tooltip(fn (bool $state): string => $state ? 'Vínculo laboral vigente' : 'Registro histórico'),
                TextColumn::make('empresa.nombre_comercial')->label('Empresa')
                    ->formatStateUsing(fn (?string $state, HistorialLaboral $record): string => $state ?: $record->empresa?->razon_social ?: 'Sin empresa')
                    ->description(fn (HistorialLaboral $record): string => $record->sucursal?->nombre ?? 'Sin sucursal')
                    ->searchable()->wrap(),
                TextColumn::make('cargo.nombre')->label('Cargo')
                    ->description(fn (HistorialLaboral $record): string => $record->cargo?->area?->nombre ?? 'Sin área')
                    ->placeholder('Sin cargo')->searchable()->wrap(),
                TextColumn::make('tipo_contrato')->label('Contrato')->badge()->color('info')->wrap(),
                TextColumn::make('fecha_inicio')->label('Periodo')->date('d/m/Y')
                    ->description(fn (HistorialLaboral $record): string => $record->fecha_fin
                        ? 'Hasta '.$record->fecha_fin->format('d/m/Y')
                        : ($record->activo ? 'Actualmente vigente' : 'Sin fecha final'))
                    ->sortable(),
                TextColumn::make('salario')->label('Salario')->money('BOB')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('correo_corporativo')->label('Contacto')->icon('heroicon-o-envelope')
                    ->copyable()->placeholder('Sin correo')
                    ->description(fn (HistorialLaboral $record): string => $record->numero_corporativo ?: 'Sin número corporativo')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(fn (): string => $this->getOwnerRecord()->historialLaboral()->exists()
                        ? 'Registrar nuevo vínculo' : 'Registrar vínculo laboral')
                    ->icon('heroicon-o-plus')->modalHeading('Nuevo vínculo laboral')
                    ->modalDescription('El nuevo vínculo quedará activo. Si existe otro vigente, será cerrado automáticamente.')
                    ->createAnother(false)
                    ->visible(fn (): bool => (bool) $this->getOwnerRecord()->activo)
                    ->mutateFormDataUsing(fn (array $data): array => $this->normalizarDatos($data))
                    ->before(function (array $data): void {
                        $fechaFin = Carbon::parse($data['fecha_inicio'])->subDay()->toDateString();
                        $this->getOwnerRecord()->historialLaboral()->where('activo', true)->get()
                            ->each(function (HistorialLaboral $historial) use ($fechaFin): void {
                                $historial->update([
                                    'activo' => false,
                                    'fecha_fin' => $historial->fecha_fin ?: $fechaFin,
                                    'fecha_baja' => $historial->fecha_baja ?: $fechaFin,
                                ]);
                            });
                    })
                    ->successNotification(Notification::make()->success()->title('Vínculo laboral registrado')),
            ])
            ->actions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar')
                    ->visible(fn (HistorialLaboral $record): bool => $record->activo)
                    ->mutateRecordDataUsing(function (array $data): array {
                        if (! array_key_exists($data['tipo_contrato'], static::contractOptions())) {
                            $data['tipo_contrato_otro'] = $data['tipo_contrato'];
                            $data['tipo_contrato'] = 'Otro';
                        }

                        return $data;
                    })
                    ->mutateFormDataUsing(fn (array $data): array => $this->normalizarDatos($data)),
            ])
            ->emptyStateHeading('Sin historial laboral')
            ->emptyStateDescription('Registre la empresa, sucursal, cargo y condiciones del primer vínculo.')
            ->emptyStateIcon('heroicon-o-briefcase')
            ->paginated([10, 25, 50]);
    }

    /** @return array<string, string> */
    protected static function contractOptions(): array
    {
        return [
            'Contrato indefinido' => 'Contrato indefinido',
            'Contrato plazo fijo' => 'Contrato a plazo fijo',
            'Contrato por servicios' => 'Contrato por servicios',
            'Contrato por obra' => 'Contrato por obra',
            'Contrato por temporada' => 'Contrato por temporada',
            'Contrato de teletrabajo' => 'Contrato de teletrabajo',
            'Pasante' => 'Pasantía',
            'Otro' => 'Otro tipo de contrato',
        ];
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function normalizarDatos(array $data): array
    {
        if (($data['tipo_contrato'] ?? null) === 'Otro') {
            $data['tipo_contrato'] = $data['tipo_contrato_otro'] ?? 'Otro';
        }

        unset($data['tipo_contrato_otro']);

        if (($data['tipo_contrato'] ?? null) === 'Contrato indefinido') {
            $data['fecha_fin'] = null;
        }

        $data['activo'] = true;

        return $data;
    }

    protected function generarCorreoCorporativo(int|string|null $empresaId): ?string
    {
        $empleado = $this->getOwnerRecord();
        if (blank($empleado->nombres) || blank($empleado->apellidos)) {
            return null;
        }

        $nombre = Str::of($empleado->nombres)->before(' ')->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '');
        $apellido = Str::of($empleado->apellidos)->before(' ')->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '');
        $empresa = $empresaId ? Empresa::find($empresaId) : null;
        $dominio = Str::of($empresa?->sitio_web ?: 'empresa.com')
            ->replaceMatches('#^https?://#', '')->before('/')->before('?')->trim();

        if (! str_contains((string) $dominio, '.')) {
            $dominio = 'empresa.com';
        }

        return "{$nombre}.{$apellido}@{$dominio}";
    }
}
