<?php

namespace App\Filament\Concerns;

use App\Models\Sistema\Empresa;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesEmpresa
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->hasAnyRole(['super_admin', 'admin'])) {
            return $query;
        }

        return $query->where('empresa_id', $user->empresa_id ?? 0);
    }

    protected static function empresaField(): Select
    {
        $user = Auth::user();

        return Select::make('empresa_id')
            ->label('Empresa')
            ->options(fn () => self::opcionesEmpresa())
            ->default(fn () => Auth::user()?->empresa_id ?: array_key_first(self::opcionesEmpresa()))
            ->required()->searchable()->preload()->live()
            ->disabled(fn () => Auth::user() && ! Auth::user()->hasAnyRole(['super_admin', 'admin']))
            ->dehydrated();
    }

    private static function opcionesEmpresa(): array
    {
        $user = Auth::user();

        return Empresa::query()
            ->when($user && ! $user->hasAnyRole(['super_admin', 'admin']),
                fn (Builder $query) => $query->whereKey($user->empresa_id ?? 0))
            ->orderByRaw("CASE WHEN nombre_comercial IS NULL OR nombre_comercial = '' THEN razon_social ELSE nombre_comercial END")
            ->get()
            ->mapWithKeys(fn (Empresa $empresa) => [
                $empresa->id => $empresa->nombre_comercial ?: $empresa->razon_social,
            ])
            ->all();
    }
}
