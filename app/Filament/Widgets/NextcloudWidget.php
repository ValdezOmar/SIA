<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Detection\MobileDetect; // importamos Mobile Detect

class NextcloudWidget extends Widget 
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.nextcloud-widget';
    protected static ?string $heading = 'Nextcloud widget';

    public function getNextcloudUrl(): string
    {
        return 'https://nube.ailem.app';
    }
    
    /**
     * Determina qué enlace de descarga mostrar según dispositivo
     */
    public function getDownloadLink(): string
    {
        $detect = new MobileDetect();

        if ($detect->isiOS()) {
            // iPhone / iPad
            return 'https://apps.apple.com/es/app/nextcloud/id1125420102';  
        } elseif ($detect->isAndroidOS()) {
            return 'https://play.google.com/store/apps/details?id=com.nextcloud.client';  // enlace de Google Play
        } else {
            // por defecto o Windows / escritorio
            return 'https://github.com/nextcloud-releases/desktop/releases/download/v3.17.2/Nextcloud-3.17.2-x64.msi';
        }
    }

    public function getClientDownloadUrl(): string
    {
        // Si quieres, puedes combinar con getDownloadLink:
        return $this->getDownloadLink();
    }

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