<div x-data="{
        location: $wire.entangle('localizacion'),
        mapUrl: '',
        init() {
            this.$watch('location', () => this.updateMap());
            this.updateMap();
        },
        updateMap() {
            if (!this.location) return;

            const coords = this.location.split(',').map(coord => parseFloat(coord.trim()));
            if (coords.length !== 2 || isNaN(coords[0]) || isNaN(coords[1])) return;

            const [lat, lng] = coords;
            this.mapUrl = `https://maps.google.com/maps?q=${lat},${lng}&z=16&output=embed`;
        }
    }" x-show="location" class="mt-4 overflow-hidden border border-gray-200 rounded-lg dark:border-gray-700 h-96">
    <template x-if="mapUrl">
        <iframe :src="mapUrl" width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen
            loading="lazy"></iframe>
    </template>

    <div x-show="!mapUrl" class="flex items-center justify-center w-full h-full bg-gray-100 dark:bg-gray-800">
        <div class="text-center">
            <svg class="w-10 h-10 mx-auto mb-2 text-gray-400 animate-spin" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">Cargando mapa...</p>
        </div>
    </div>
</div>