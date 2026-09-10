<section class="sia-dashboard-summary" style="display:grid;gap:1rem;width:100%" @if ($pollingInterval) wire:poll.{{ $pollingInterval }} @endif>
    <header class="sia-dashboard-summary__header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;padding:.1rem .15rem">
        <div>
            <h2 style="margin:0;color:var(--sia-text);font-size:1.08rem;font-weight:800">{{ $heading }}</h2>
            @if ($description)<p style="margin:.25rem 0 0;color:var(--sia-muted);font-size:.82rem">{{ $description }}</p>@endif
        </div>
        <span class="sia-dashboard-summary__live"><i></i> Actualizado en vivo</span>
    </header>

    <div class="sia-dashboard-summary__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:1rem;width:100%">
        @foreach ($cards as $card)
            @php
                $url = $card->getUrl();
                $color = $card->getColor();
                $color = is_array($color) ? array_key_first($color) : ($color ?: 'primary');
            @endphp
            <a @if ($url) href="{{ $url }}" @endif class="sia-dashboard-summary__card is-{{ $color }}" style="display:flex;min-height:10rem;flex-direction:column;justify-content:space-between;gap:.8rem;padding:1.05rem;border:1px solid var(--sia-border);border-radius:.9rem;background:var(--sia-surface);box-shadow:0 .18rem .55rem rgb(15 23 42 / .06);text-decoration:none">
                <div class="sia-dashboard-summary__card-top" style="display:flex;align-items:center;gap:.6rem">
                    <span class="sia-dashboard-summary__icon" style="display:grid;place-items:center;width:2.15rem;height:2.15rem;flex:0 0 2.15rem;border-radius:.65rem;background:color-mix(in srgb,var(--sia-primary) 14%,var(--sia-surface));color:var(--sia-primary)"><x-filament::icon :icon="$card->getIcon()" /></span>
                    <span class="sia-dashboard-summary__label" style="color:var(--sia-muted);font-size:.78rem;font-weight:750;line-height:1.3">{{ $card->getLabel() }}</span>
                </div>
                <strong class="sia-dashboard-summary__value" style="color:var(--sia-text);font-size:1.75rem;font-weight:850;letter-spacing:-.045em;line-height:1.1">{{ $card->getValue() }}</strong>
                @if ($card->getDescription())
                    <span class="sia-dashboard-summary__description" style="display:flex;align-items:center;gap:.35rem;color:var(--sia-primary);font-size:.76rem;font-weight:700;line-height:1.35">
                        {{ $card->getDescription() }}
                        @if ($card->getDescriptionIcon())<x-filament::icon :icon="$card->getDescriptionIcon()" />@endif
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</section>

<style>
 .sia-dashboard-summary{display:grid;gap:1rem}.sia-dashboard-summary__header{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;padding:.1rem .15rem}.sia-dashboard-summary__header h2{margin:0;color:var(--sia-text);font-size:1.08rem;font-weight:800;letter-spacing:-.02em}.sia-dashboard-summary__header p{margin:.25rem 0 0;color:var(--sia-muted);font-size:.82rem}.sia-dashboard-summary__live{display:inline-flex;align-items:center;gap:.38rem;color:var(--sia-muted);font-size:.7rem;font-weight:700;white-space:nowrap}.sia-dashboard-summary__live i{width:.45rem;height:.45rem;border-radius:50%;background:#22c55e;box-shadow:0 0 0 .2rem rgb(34 197 94 / .14);animation:sia-dashboard-pulse 2s infinite}.sia-dashboard-summary__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(13.5rem,1fr));gap:.9rem}.sia-dashboard-summary__card{display:grid;align-content:space-between;min-height:10.5rem;padding:1.05rem;border:1px solid var(--sia-border);border-radius:.9rem;background:var(--sia-surface);box-shadow:0 .18rem .55rem rgb(15 23 42 / .035);text-decoration:none;transition:transform .17s ease,box-shadow .17s ease,border-color .17s ease}.sia-dashboard-summary__card:hover{transform:translateY(-3px);border-color:color-mix(in srgb,var(--sia-primary) 35%,var(--sia-border));box-shadow:0 .8rem 1.5rem rgb(15 23 42 / .1)}.sia-dashboard-summary__card-top{display:flex;align-items:center;gap:.6rem}.sia-dashboard-summary__icon{display:grid;place-items:center;width:2rem;height:2rem;border-radius:.6rem;background:color-mix(in srgb,var(--sia-primary) 11%,transparent);color:var(--sia-primary)}.sia-dashboard-summary__icon svg{width:1.1rem;height:1.1rem}.sia-dashboard-summary__label{color:var(--sia-muted);font-size:.78rem;font-weight:750;line-height:1.3}.sia-dashboard-summary__value{margin-top:.8rem;color:var(--sia-text);font-size:1.75rem;font-weight:850;letter-spacing:-.045em;line-height:1.1}.sia-dashboard-summary__description{display:flex;align-items:center;gap:.35rem;margin-top:.8rem;font-size:.76rem;font-weight:700;line-height:1.35;color:var(--sia-primary)}.sia-dashboard-summary__description svg{flex:0 0 auto;width:1rem;height:1rem}.sia-dashboard-summary__card.is-success .sia-dashboard-summary__icon{background:#ecfdf3;color:#16a34a}.sia-dashboard-summary__card.is-success .sia-dashboard-summary__description{color:#15803d}.sia-dashboard-summary__card.is-warning .sia-dashboard-summary__icon{background:#fffbeb;color:#d97706}.sia-dashboard-summary__card.is-warning .sia-dashboard-summary__description{color:#b45309}.sia-dashboard-summary__card.is-danger .sia-dashboard-summary__icon{background:#fff1f2;color:#dc2626}.sia-dashboard-summary__card.is-danger .sia-dashboard-summary__description{color:#dc2626}.sia-dashboard-summary__card.is-info .sia-dashboard-summary__icon{background:#eff6ff;color:#2563eb}.sia-dashboard-summary__card.is-info .sia-dashboard-summary__description{color:#2563eb}.dark .sia-dashboard-summary__header h2,.dark .sia-dashboard-summary__value{color:#f8fafc}.dark .sia-dashboard-summary__card{border-color:var(--sia-dark-border);background:var(--sia-dark-surface)}.dark .sia-dashboard-summary__card:hover{border-color:color-mix(in srgb,var(--sia-primary) 48%,var(--sia-dark-border));box-shadow:0 .8rem 1.5rem rgb(0 0 0 / .24)}.dark .sia-dashboard-summary__card.is-success .sia-dashboard-summary__icon{background:rgb(20 83 45 / .45);color:#86efac}.dark .sia-dashboard-summary__card.is-warning .sia-dashboard-summary__icon{background:rgb(120 53 15 / .4);color:#fcd34d}.dark .sia-dashboard-summary__card.is-danger .sia-dashboard-summary__icon{background:rgb(127 29 29 / .42);color:#fca5a5}.dark .sia-dashboard-summary__card.is-info .sia-dashboard-summary__icon{background:rgb(30 58 138 / .4);color:#93c5fd}@keyframes sia-dashboard-pulse{50%{box-shadow:0 0 0 .38rem rgb(34 197 94 / 0)}}@media(max-width:640px){.sia-dashboard-summary__header{align-items:flex-start;flex-direction:column}.sia-dashboard-summary__grid{grid-template-columns:1fr}.sia-dashboard-summary__card{min-height:9.25rem}}
</style>
