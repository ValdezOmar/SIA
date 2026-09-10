@php
    $primary = $appearance['primary'] ?? '#009BA4';
    $secondary = $appearance['secondary'] ?? '#3066BE';
    $background = $appearance['background'] ?? '/images/fondo.jpg';
    $backgroundFile = public_path(ltrim($background, '/'));
    $backgroundUrl = asset($background).(is_file($backgroundFile) ? '?v='.filemtime($backgroundFile) : '');
    $isSolidLogin = ($appearance['loginStyle'] ?? 'cristal') === 'solido';
@endphp
<link rel="stylesheet" href="{{ asset('css/sia-filament-theme.css') }}?v={{ filemtime(public_path('css/sia-filament-theme.css')) }}">
<style>
    :root {
        --sia-primary: {{ $primary }};
        --sia-secondary: {{ $secondary }};
        --sia-login-background: url('{{ $backgroundUrl }}');
        --sia-font-scale: {{ $appearance['fontScale'] ?? '88%' }};
        --sia-login-surface: {{ $isSolidLogin ? 'rgb(255 255 255 / 0.96)' : 'rgb(255 255 255 / 0.50)' }};
        --sia-login-blur: {{ $isSolidLogin ? '0px' : '0.375rem' }};
    }
</style>
