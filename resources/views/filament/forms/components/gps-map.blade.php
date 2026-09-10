<div x-data="{
        location: $wire.entangle('localizacion'),
        mapUrl: '',
        init() {
            this.$watch('location', () => this.updateMap());
            this.updateMap();
        },
        updateMap() {
            if (!this.location) {
                this.mapUrl = '';
                return;
            }

            const coords = this.location.split(',').map(coord => parseFloat(coord.trim()));
            if (coords.length !== 2 || isNaN(coords[0]) || isNaN(coords[1])) return;

            const [lat, lng] = coords;
            this.mapUrl = `https://maps.google.com/maps?q=${lat},${lng}&z=16&output=embed`;
        }
    }" class="sia-attendance-map">
    <template x-if="mapUrl">
        <iframe :src="mapUrl" width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen
            loading="lazy"></iframe>
    </template>

    <div x-show="!mapUrl" class="sia-attendance-map__empty flex items-center justify-center w-full h-full">
        <div class="text-center">
            <svg class="w-10 h-10 mx-auto mb-2 text-gray-400 animate-spin" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <p>Mapa pendiente</p>
            <span>Confirma la ubicacion con GPS para visualizar el punto de la marcacion.</span>
        </div>
    </div>
</div>

<style>
    .sia-attendance-map { min-height: 31rem; overflow: hidden; border: 1px solid var(--sia-border); border-radius: .9rem; background: var(--sia-surface-subtle); }
    .sia-attendance-map iframe { display: block; width: 100%; height: 31rem; border: 0 !important; }
    .sia-attendance-map__empty { min-height: 31rem; padding: 1.5rem; text-align: center; color: var(--sia-muted); }
    .sia-attendance-map__empty svg { width: 2.7rem; height: 2.7rem; margin-inline: auto; color: var(--sia-primary); }
    .sia-attendance-map__empty p { margin-top: .65rem; color: var(--sia-text); font-size: .9rem; font-weight: 750; }
    .sia-attendance-map__empty span { display: block; max-width: 15rem; margin-top: .25rem; font-size: .8rem; line-height: 1.45; }
    .dark .sia-attendance-map { border-color: var(--sia-dark-border); background: var(--sia-dark-elevated); }
    .dark .sia-attendance-map__empty p { color: #fff; }
</style>
