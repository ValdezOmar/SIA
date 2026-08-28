<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class EmpresaMailWidget extends Widget
{
    protected static string $view = 'filament.Widgets.empresa-mail-widget';

    protected static ?string $heading = 'Correo empresarial';

    public function getMailUrl(): string
    {
        return 'https://ns1.goldservercloud.com:2096/';
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
