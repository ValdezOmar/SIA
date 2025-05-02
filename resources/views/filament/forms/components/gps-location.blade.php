<div x-data="gpsLocation()" class="space-y-2">
    
    <div x-data="gpsLocation()" class="space-y-2">
        <!-- ... (código existente) ... -->
        <template x-if="status === 'success'">
            <div class="p-4 bg-green-50 text-green-800 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Ubicación verificada. Complete la justificación para continuar.</span>
                </div>
                <div class="mt-1 text-sm opacity-75" x-text="'Coordenadas: ' + location"></div>
            </div>
        </template>

    <template x-if="status === 'loading'">
        <div class="flex items-center p-4 bg-blue-50 text-blue-800 rounded-lg">
            <svg class="w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="message"></span>
        </div>
    </template>
    
    <template x-if="status === 'success'">
        <div class="p-4 bg-green-50 text-green-800 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span x-text="message"></span>
            </div>
            <div class="mt-1 text-sm opacity-75" x-text="'Coordenadas: ' + location"></div>
        </div>
    </template>
    
    <template x-if="status === 'error'">
        <div class="p-4 bg-red-50 text-red-800 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span x-text="error"></span>
            </div>
            <button @click="getLocation()" class="mt-2 text-sm text-red-600 underline">
                Reintentar
            </button>
        </div>
    </template>
</div>

<script>
function gpsLocation() {
    return {
        status: 'loading',
        message: 'Solicitando acceso a GPS...',
        error: null,
        location: null,
        init() {
            this.getLocation();
        },
        getLocation() {
            this.status = 'loading';
            this.message = 'Solicitando acceso a GPS...';
            this.error = null;
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.status = 'success';
                        this.message = 'Ubicación obtenida correctamente';
                        this.location = `${position.coords.latitude},${position.coords.longitude}`;
                        this.$wire.set('localizacion', this.location);
                    },
                    (error) => {
                        this.status = 'error';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                this.error = "Debes permitir el acceso a la ubicación";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                this.error = "La ubicación no está disponible";
                                break;
                            case error.TIMEOUT:
                                this.error = "Tiempo de espera agotado";
                                break;
                            default:
                                this.error = "Error al obtener la ubicación";
                        }
                        this.$wire.set('localizacion', '');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                this.status = 'error';
                this.error = "Geolocalización no soportada por tu navegador";
                this.$wire.set('localizacion', '');
            }
        }
    }
}
</script>