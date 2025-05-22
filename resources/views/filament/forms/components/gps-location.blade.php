<div x-data="gpsLocation()" class="space-y-4">
    <!-- Botón para solicitar ubicación -->
    <button 
        x-show="status !== 'success'"
        @click="getLocation()" 
        type="button"
        class="w-full flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-500 focus:outline-none focus:border-primary-700 focus:ring focus:ring-primary-200 active:bg-primary-600 disabled:opacity-25 transition"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
        </svg>
        Obtener Ubicación GPS
    </button>

    <!-- Estados -->
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
                <span>Ubicación verificada correctamente</span>
            </div>
            <div class="mt-2 text-sm">
                <div class="font-medium">Coordenadas:</div>
                <div x-text="location" class="opacity-75"></div>
            </div>
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
            <button 
                @click="getLocation()" 
                class="mt-2 px-3 py-1 bg-red-100 text-red-700 rounded text-sm font-medium hover:bg-red-200 transition"
            >
                Reintentar
            </button>
        </div>
    </template>
</div>

<script>
function gpsLocation() {
    return {
        status: 'idle',
        message: 'Por favor, haz clic en el botón para obtener tu ubicación',
        error: null,
        location: null,
        
        getLocation() {
            this.status = 'loading';
            this.message = 'Solicitando acceso a GPS...';
            this.error = null;
            
            if (!navigator.geolocation) {
                this.status = 'error';
                this.error = "Tu navegador no soporta geolocalización";
                this.clearLocation();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.status = 'success';
                    this.message = 'Ubicación obtenida correctamente';
                    this.location = `${position.coords.latitude}, ${position.coords.longitude}`;
                    this.updateLivewire(this.location);
                },
                (error) => {
                    this.status = 'error';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            this.error = "Debes permitir el acceso a la ubicación para registrar asistencia";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            this.error = "La información de ubicación no está disponible";
                            break;
                        case error.TIMEOUT:
                            this.error = "La solicitud de ubicación ha expirado. Por favor, intenta nuevamente";
                            break;
                        default:
                            this.error = "Error desconocido al obtener la ubicación";
                    }
                    this.clearLocation();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        },
        
        updateLivewire(location) {
            // Actualizar el campo en Livewire/Filament
            if (this.$wire) {
                this.$wire.set('localizacion', location);
            } else if (window.Livewire) {
                const componentId = this.$el.closest('[wire\\:id]')?.getAttribute('wire:id');
                if (componentId) {
                    window.Livewire.find(componentId).set('localizacion', location);
                }
            }
        },
        
        clearLocation() {
            this.location = null;
            this.updateLivewire('');
        }
    }
}
</script>