<?php

namespace App\Support;

use App\Models\Sistema\Parametro;
use Illuminate\Support\Facades\Storage;

final class PanelAppearance
{
    public static function values(): array
    {
        $defaults = [
            'primary' => '#009BA4',
            'secondary' => '#3066BE',
            'logo' => '/images/logo.png',
            'favicon' => '/images/favicon.ico',
            'background' => '/images/fondo.jpg',
            'fontScale' => '88%',
            'loginStyle' => 'cristal',
        ];

        try {
            $parametro = Parametro::query()->first();

            if (! $parametro) {
                return $defaults;
            }

            return [
                'primary' => self::color($parametro->color_principal, $defaults['primary']),
                'secondary' => self::color($parametro->color_secundario, $defaults['secondary']),
                'logo' => self::publicImage($parametro->logo_path, $defaults['logo']),
                'favicon' => self::publicImage($parametro->favicon_path, $defaults['favicon']),
                'background' => self::publicImage($parametro->fondo_path, $defaults['background']),
                'fontScale' => in_array($parametro->escala_interfaz, ['88%', '94%', '100%'], true) ? $parametro->escala_interfaz : $defaults['fontScale'],
                'loginStyle' => in_array($parametro->estilo_login, ['cristal', 'solido'], true) ? $parametro->estilo_login : $defaults['loginStyle'],
            ];
        } catch (\Throwable) {
            return $defaults;
        }
    }

    private static function color(mixed $color, string $fallback): string
    {
        return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : $fallback;
    }

    private static function publicImage(mixed $path, string $fallback): string
    {
        if (! is_string($path)) {
            return $fallback;
        }

        if (str_starts_with($path, '/images/')) {
            return $path;
        }

        if (str_starts_with($path, '/') || str_contains($path, ':') || in_array('..', explode('/', str_replace('\\', '/', $path)), true)) {
            return $fallback;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : $fallback;
    }
}
