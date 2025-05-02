<div x-data="mapPicker()" class="w-full">
    <input type="hidden" x-model="location" {{ $attributes }}>
    
    <!-- Campo de texto para mostrar/editar coordenadas -->
    <div class="mb-2">
        <div class="flex gap-2">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input 
                        type="text" 
                        x-model="coordinates" 
                        readonly
                        placeholder="Coordenadas GPS"
                        class="w-full" />
                </x-filament::input.wrapper>
            </div>
            <x-filament::button 
                type="button" 
                @click="toggleMap()"
                color="gray"
                icon="heroicon-o-map"
                x-text="showMap ? 'Ocultar Mapa' : 'Mostrar Mapa'">
            </x-filament::button>
        </div>
    </div>
    
    <!-- Mapa (oculto inicialmente) -->
    <div x-show="showMap" x-transition class="mb-4">
        <div class="mb-2">
            <x-filament::input.wrapper>
                <x-filament::input 
                    type="text" 
                    x-model="address" 
                    @keydown.enter.prevent="searchAddress"
                    @keyup.debounce.500ms="searchAddress"
                    placeholder="Buscar dirección..."
                    class="w-full" />
            </x-filament::input.wrapper>
        </div>
        
        <div id="map" class="w-full h-96 rounded-md border border-gray-300"></div>
        
        <div class="mt-2 text-sm text-gray-500">
            <span x-text="fullAddress"></span>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
// Configurar la ruta base para los iconos de Leaflet
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

function mapPicker() {
    return {
        location: @entangle($attributes->wire('model')),
        showMap: false,
        map: null,
        marker: null,
        address: '',
        coordinates: '',
        fullAddress: '',

        init() {
            // Cargar ubicación existente si existe
            if (this.location) {
                // Verifica si ya es un objeto
                        const coords = typeof this.location === 'string' ? 
                            JSON.parse(this.location) : this.location;
                    
                if (coords && coords.lat && coords.lng) {
                    this.coordinates = `${coords.lat.toFixed(6)}, ${coords.lng.toFixed(6)}`;
                    this.getAddress(coords.lat, coords.lng);
                }
            }
        },

        toggleMap() {
            this.showMap = !this.showMap;
            if (this.showMap && !this.map) {
                setTimeout(() => {
                    this.initMap();
                }, 50);
            } else if (this.showMap && this.map) {
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 50);
            }
        },

        initMap() {
            if (!document.getElementById('map')) return;
    
            this.map = L.map('map').setView([-16.5000, -68.1500], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(this.map);

            // Configurar marcador arrastrable con icono
            let initialCoords = [-16.5000, -68.1500];
            
            if (this.location) {
                const coords = typeof this.location === 'string' ? 
                    JSON.parse(this.location) : this.location;
                if (coords && coords.lat && coords.lng) {
                    initialCoords = [coords.lat, coords.lng];
                }
            }
            
            this.marker = L.marker(initialCoords, { 
                draggable: true,
                icon: new L.Icon.Default() // Asegúrate de usar el icono correctamente
            }).addTo(this.map);

            // Evento para actualizar posición al arrastrar
            this.marker.on('dragend', (e) => {
                const { lat, lng } = e.target.getLatLng();
                this.updateLocation(lat, lng);
            });

            // Evento para hacer clic en el mapa
            this.map.on('click', (e) => {
                this.marker.setLatLng(e.latlng);
                this.updateLocation(e.latlng.lat, e.latlng.lng);
            });

            // Si hay ubicación previa, centrar el mapa
            if (this.location) {
                const coords = JSON.parse(this.location);
                this.map.setView([coords.lat, coords.lng], 15);
            }
            
            setTimeout(() => {
                this.map.invalidateSize();
            }, 100);
        },

        updateLocation(lat, lng) {
            this.location = JSON.stringify({ lat, lng });
            this.coordinates = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            this.getAddress(lat, lng);
        },

        async searchAddress() {
            if (!this.address) return;
            
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.address)}&limit=1&countrycodes=bo`
                );
                const data = await response.json();
                
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    
                    if (this.marker) {
                        this.marker.setLatLng([lat, lon]);
                        this.map.setView([lat, lon], 15);
                    }
                    
                    this.updateLocation(lat, lon);
                }
            } catch (error) {
                console.error('Error buscando dirección:', error);
            }
        },

        async getAddress(lat, lng) {
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`
                );
                const data = await response.json();
                
                if (data.address) {
                    const addr = data.address;
                    this.fullAddress = [
                        addr.road || '',
                        addr.house_number || '',
                        addr.neighbourhood || '',
                        addr.city || addr.town || addr.village || '',
                        addr.country || ''
                    ].filter(Boolean).join(', ');
                }
            } catch (error) {
                console.error('Error obteniendo dirección:', error);
                this.fullAddress = 'Dirección no disponible';
            }
        }
    }
}
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<style>
    #map { z-index: 0 !important; height: 400px; }
    .leaflet-container { z-index: 0 !important; }
    .leaflet-marker-draggable { cursor: move !important; }
    [x-cloak] { display: none !important; }
    
    /* Estilos para el icono del marcador */
    .leaflet-default-icon-path {
        background-image: url(https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png);
    }
</style>
@endpush