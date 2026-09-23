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
        --sia-login-surface: {{ $isSolidLogin ? 'rgb(255 255 255 / 0.96)' : 'rgb(255 255 255 / 0.78)' }};
        --sia-login-blur: {{ $isSolidLogin ? '0px' : '0.375rem' }};
        --sia-login-dark-surface: {{ $isSolidLogin ? 'rgb(15 23 42 / 0.96)' : 'rgb(15 23 42 / 0.82)' }};
        --sia-login-dark-field: rgb(15 23 42 / 0.74);
        --sia-login-dark-border: rgb(255 255 255 / 0.20);
        --sia-login-dark-text: #f8fafc;
        --sia-login-dark-muted: #cbd5e1;
    }

    /* La imagen y los colores configurados se mantienen en ambos modos. */
    .dark .fi-simple-layout > .fi-simple-main-ctn > .fi-simple-main {
        background: var(--sia-login-dark-surface) !important;
        color: var(--sia-login-dark-text) !important;
        backdrop-filter: blur(var(--sia-login-blur));
    }

    .dark .fi-simple-main :is(.fi-simple-header-heading, label, legend) {
        color: var(--sia-login-dark-text) !important;
    }

    .dark .fi-simple-main :is(.fi-simple-header-subheading, [class*="hint"], [class*="helper"]) {
        color: var(--sia-login-dark-muted) !important;
    }

    .dark .fi-simple-main :is(input, select, textarea, .fi-input-wrp, .fi-select-input, .fi-fo-field-wrp-input) {
        background: var(--sia-login-dark-field) !important;
        border-color: var(--sia-login-dark-border) !important;
        color: var(--sia-login-dark-text) !important;
    }

    .dark .fi-simple-main :is(input, textarea)::placeholder {
        color: var(--sia-login-dark-muted) !important;
        opacity: 0.85;
    }
</style>
