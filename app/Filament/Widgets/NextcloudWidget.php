<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class NextcloudWidget extends Widget 
{
    protected static string $view = 'filament.widgets.nextcloud-widget';
    protected static ?string $heading = 'Nextcloud widget';
    use HasWidgetShield;    

    public function getNextcloudUrl(): string
    {
        return 'https://nube.ailem.app';
    }

    public function getClientDownloadUrl(): string
    {
        return 'https://github.com/nextcloud-releases/desktop/releases/download/v3.16.4/Nextcloud-3.16.4-x64.msi';
    }

    // // Esto evita que Filament busque rutas
    // public static function canView(): bool
    // {
    //     return true;
    // }

    /**
     * @return int|string|array<string, int|null>
     */
    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 2,
        ];
    }
}