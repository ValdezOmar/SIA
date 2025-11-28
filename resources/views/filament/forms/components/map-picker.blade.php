<div x-data="mapPicker()" x-init="init(@js($getState() ?? []))" class="w-full">
    <input type="hidden" x-model="locationJson" x-on:input="$wire.set('data.ubicacion_gps', locationJson)">


    <!-- Debug visual (solo en desarrollo) -->
    <div x-show="false" x-text="'DEBUG: ' + JSON.stringify($data)"></div>

    <!-- Campo de coordenadas -->
    <div class="mb-2">
        <div class="flex gap-2">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" x-model="coordinates" readonly :placeholder="empty($state) ? 'Seleccione la ubicación del croquis en el mapa' : 'Coordenadas GPS'" class="w-full" />
                </x-filament::input.wrapper>
            </div>
            <x-filament::button type="button" @click="toggleMap()" color="gray" icon="heroicon-o-map"
                x-text="showMap ? 'Ocultar Mapa' : 'Mostrar Mapa'">
            </x-filament::button>
        </div>
    </div>

    <!-- Mapa -->
    <div x-show="showMap" x-transition x-cloak class="mb-4">
        <div class="mb-2">
            <x-filament::input.wrapper>
                <x-filament::input type="text" x-model="address" @keydown.enter.prevent="searchAddress"
                    @keyup.debounce.500ms="searchAddress" placeholder="Buscar dirección..." class="w-full" />
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
        // Configuración de Leaflet
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
            iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        });

        function mapPicker() {
            return {
                // Estado del componente
                location: null,
                showMap: false,
                map: null,
                marker: null,
                address: '',
                coordinates: '',
                fullAddress: '',
                loading: false,

                // Método de inicialización
                init(initialLocation) {
                    console.group('[MapPicker] Inicializando componente');
                    console.log('Datos iniciales recibidos:', initialLocation);

                    this.processInitialLocation(initialLocation);

                    // Sincronización con Livewire
                    this.$watch('location', (value) => {
                        console.group('[MapPicker] Cambio en location detectado');
                        console.log('Nueva ubicación:', value);
                        if (value && typeof value === 'object') {
                            console.log('Actualizando Livewire con nueva ubicación');
                            this.$wire.set('ubicacion_gps', value, false);
                        }
                        console.groupEnd();
                    }, {
                        deep: true
                    });

                    console.groupEnd();
                },

                processInitialLocation(location) {
                    console.group('[MapPicker] Procesando ubicación inicial');
                    try {
                        console.log('Tipo de dato recibido:', typeof location, 'Valor:', location);

                        // Caso 1: Objeto válido
                        if (location && typeof location === 'object' && location.lat !== undefined && location.lng !==
                            undefined) {
                            console.log('Procesando como objeto de coordenadas');
                            this.location = {
                                lat: parseFloat(location.lat),
                                lng: parseFloat(location.lng)
                            };
                            this.coordinates = `${this.location.lat.toFixed(6)}, ${this.location.lng.toFixed(6)}`;
                            console.log('Ubicación procesada:', this.location);
                        }
                        // Caso 2: String JSON
                        else if (typeof location === 'string' && location.trim() !== '') {
                            console.log('Procesando como string JSON');
                            try {
                                const parsed = JSON.parse(location);
                                console.log('JSON parseado:', parsed);
                                if (parsed && parsed.lat !== undefined && parsed.lng !== undefined) {
                                    this.location = {
                                        lat: parseFloat(parsed.lat),
                                        lng: parseFloat(parsed.lng)
                                    };
                                    this.coordinates = `${this.location.lat.toFixed(6)}, ${this.location.lng.toFixed(6)}`;
                                    console.log('Ubicación procesada:', this.location);
                                } else {
                                    console.warn('JSON no contiene coordenadas válidas');
                                    this.setDefaultLocation();
                                }
                            } catch (e) {
                                console.error('Error parseando JSON:', e);
                                this.setDefaultLocation();
                            }
                        }
                        // Caso 3: No hay datos válidos
                        else {
                            console.warn('No se recibieron datos válidos, usando ubicación por defecto');
                            this.setDefaultLocation();
                        }
                    } catch (e) {
                        console.error('Error procesando ubicación inicial:', e);
                        this.setDefaultLocation();
                    } finally {
                        console.groupEnd();
                    }
                },

                setDefaultLocation() {
                    console.log('Estableciendo ubicación por defecto (La Paz)');
                    this.location = {
                        lat: -16.5000,
                        lng: -68.1500
                    };
                    this.coordinates = 'Seleccione la ubicación del croquis en el mapa';
                },

                get locationJson() {
                    const json = JSON.stringify(this.location);
                    console.log('Generando locationJson:', json);
                    return json;
                },

                toggleMap() {
                    console.group('[MapPicker] Alternando visibilidad del mapa');
                    console.log('Estado actual del mapa:', this.showMap ? 'visible' : 'oculto');

                    this.showMap = !this.showMap;
                    if (this.showMap && !this.map) {
                        console.log('Inicializando mapa por primera vez');
                        this.$nextTick(() => this.initMap());
                    } else if (this.showMap && this.map) {
                        console.log('Reajustando tamaño del mapa existente');
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.map.invalidateSize();
                            }, 100);
                        });
                    }

                    console.groupEnd();
                },

                initMap() {
                    console.group('[MapPicker] Inicializando mapa Leaflet');
                    if (!document.getElementById('map')) {
                        console.error('Elemento #map no encontrado en el DOM');
                        console.groupEnd();
                        return;
                    }

                    console.log('Creando mapa en coordenadas:', this.location);
                    this.map = L.map('map', {
                        preferCanvas: true,
                        zoomControl: true
                    }).setView([this.location.lat, this.location.lng], 15);

                    console.log('Añadiendo capa de tiles');
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        maxZoom: 19
                    }).addTo(this.map);

                    console.log('Creando marcador en:', this.location);
                    this.marker = L.marker([this.location.lat, this.location.lng], {
                        draggable: true,
                        autoPan: true
                    }).addTo(this.map);

                    // Eventos con throttling
                    this.marker.on('dragend', (e) => {
                        const { lat, lng } = e.target.getLatLng();
                        console.log('Marcador movido a:', lat, lng);
                        this.updateLocation(lat, lng);
                    });

                    this.map.on('click', (e) => {
                        console.log('Click en mapa en:', e.latlng);
                        this.marker.setLatLng(e.latlng);
                        this.updateLocation(e.latlng.lat, e.latlng.lng);
                    });

                    console.log('Obteniendo dirección inicial');
                    this.getAddress(this.location.lat, this.location.lng);

                    setTimeout(() => {
                        console.log('Ajustando tamaño del mapa');
                        this.map.invalidateSize();
                        console.groupEnd();
                    }, 300);
                },

                updateLocation(lat, lng) {
                    console.group('[MapPicker] Actualizando ubicación');
                    const roundedLat = parseFloat(lat.toFixed(6));
                    const roundedLng = parseFloat(lng.toFixed(6));
                    console.log('Coordenadas originales:', lat, lng);
                    console.log('Coordenadas redondeadas:', roundedLat, roundedLng);

                    this.location = {
                        lat: roundedLat,
                        lng: roundedLng
                    };
                    this.coordinates = `${roundedLat}, ${roundedLng}`;

                    console.log('Obteniendo nueva dirección');
                    this.getAddress(roundedLat, roundedLng);
                    console.groupEnd();
                },

                async searchAddress() {
                    console.group('[MapPicker] Buscando dirección');
                    if (!this.address || this.loading) {
                        console.log('Búsqueda ignorada (vacía o en progreso)');
                        console.groupEnd();
                        return;
                    }

                    console.log('Buscando:', this.address);
                    this.loading = true;

                    try {
                        const url =
                            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.address)}&limit=1&countrycodes=bo`;
                        console.log('Realizando petición a:', url);

                        const response = await fetch(url);
                        const data = await response.json();
                        console.log('Respuesta API:', data);

                        if (data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lon = parseFloat(data[0].lon);
                            console.log('Coordenadas encontradas:', lat, lon);

                            this.updateLocation(lat, lon);

                            if (this.marker) {
                                this.marker.setLatLng([lat, lon]);
                                this.map.setView([lat, lon], 15);
                            }
                        } else {
                            console.warn('No se encontraron resultados');
                        }
                    } catch (error) {
                        console.error('Error en búsqueda:', error);
                    } finally {
                        this.loading = false;
                        console.groupEnd();
                    }
                },

                async getAddress(lat, lng) {
                    console.group('[MapPicker] Geocodificación inversa');
                    console.log('Coordenadas:', lat, lng);
                    this.loading = true;

                    try {
                        const url =
                            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
                        console.log('Realizando petición a:', url);

                        const response = await fetch(url);
                        const data = await response.json();
                        console.log('Respuesta API:', data);

                        if (data.address) {
                            const addr = data.address;
                            console.log('Datos de dirección:', addr);

                            this.fullAddress = [
                                addr.road || '',
                                addr.house_number ? `#${addr.house_number}` : '',
                                addr.neighbourhood || '',
                                addr.city || addr.town || addr.village || '',
                                addr.country || ''
                            ].filter(Boolean).join(', ');
                        } else {
                            console.warn('No se encontró dirección');
                            this.fullAddress = 'Dirección no disponible';
                        }
                    } catch (error) {
                        console.error('Error obteniendo dirección:', error);
                        this.fullAddress = 'Error al obtener dirección';
                    } finally {
                        this.loading = false;
                        console.groupEnd();
                    }
                }
            };
        }
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
        #map {
            z-index: 0 !important;
            height: 400px;
        }

        .leaflet-container {
            z-index: 0 !important;
        }

        .leaflet-marker-draggable {
            cursor: move !important;
        }

        [x-cloak] {
            display: none !important;
        }

        .leaflet-control-attribution {
            font-size: 10px !important;
        }
    </style>
@endpush