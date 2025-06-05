<div x-data="{
    status: 'idle',
    message: 'Por favor, haz clic en el botón para obtener tu ubicación',
    error: null,
    location: null,
    deviceInfo: null,

    async getLocation() {
        try {
            this.status = 'loading';
            this.message = 'Solicitando acceso a GPS...';

            if (!navigator.geolocation) {
                throw new Error('Tu navegador no soporta geolocalización');
            }

            // Obtener información del dispositivo
            this.deviceInfo = this.getDeviceInfo();

            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(
                    resolve,
                    reject, {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            });

            this.status = 'success';
            this.location = `${position.coords.latitude}, ${position.coords.longitude}`;
            this.updateLivewire(this.location, this.deviceInfo);
        } catch (error) {
            this.status = 'error';
            this.error = this.getErrorMessage(error);
            this.clearLocation();
        }
    },

    getDeviceInfo() {
        try {
            const userAgent = navigator.userAgent;
            const platform = navigator.platform;
            const hardwareConcurrency = navigator.hardwareConcurrency || 'unknown';
            const deviceMemory = navigator.deviceMemory || 'unknown';

            // Detectar móvil/tablet
            const isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(userAgent);

            // Crear hash del dispositivo
            let hash = 0;
            for (let i = 0; i < userAgent.length; i++) {
                const char = userAgent.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }

            // Intentar obtener modelo en Android
            let deviceModel = '';
            if (/Android/i.test(userAgent)) {
                const modelMatch = /; ([a-zA-Z0-9]+) Build/i.exec(userAgent);
                if (modelMatch) {
                    deviceModel = modelMatch[1].replace(/_/g, ' ');
                }
            }
            // Intentar obtener modelo en iPhone
            else if (/iPhone|iPod|iPad/i.test(userAgent)) {
                const modelMatch = /iPhone(\d+,\d+)|iPhone (\d+)/i.exec(userAgent);
                if (modelMatch) {
                    deviceModel = 'iPhone ' + (modelMatch[1] ? modelMatch[1].replace(',', '.') : modelMatch[2]);
                }
            }

            return {
                userAgent: userAgent,
                platform: platform,
                hardwareConcurrency: hardwareConcurrency,
                deviceMemory: deviceMemory,
                deviceModel: deviceModel,
                isMobile: isMobile,
                deviceHash: hash.toString(16)
            };
        } catch (e) {
            return {
                error: 'No se pudo obtener información del dispositivo'
            };
        }
    },

    getErrorMessage(error) {
        const errors = {
            1: 'Permiso denegado. Por favor habilita la ubicación en tu navegador.',
            2: 'Ubicación no disponible. Verifica tu conexión o configuración de GPS.',
            3: 'Tiempo de espera agotado. Intenta nuevamente en un área con mejor recepción.'
        };
        return errors[error.code] || error.message || 'Error desconocido al obtener ubicación';
    },

    updateLivewire(location, deviceInfo) {
        if (this.$wire) {
            this.$wire.set('localizacion', location);

            // Enviar información del dispositivo como string JSON
            const deviceInfoString = JSON.stringify(deviceInfo);
            this.$wire.set('id_equipo', deviceInfoString);
        }
    },

    clearLocation() {
        this.location = null;
        this.updateLivewire('', null);
    }
}" class="space-y-4">
    <!-- Botón para solicitar ubicación -->
    <!-- Botón mejorado -->
    <button x-show="status !== 'success'" @click="getLocation()" :disabled="status === 'loading'" type="button"
        class="flex items-center justify-center w-full px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition border border-transparent rounded-md bg-primary-600 hover:bg-primary-500 focus:outline-none focus:border-primary-700 focus:ring focus:ring-primary-200 active:bg-primary-600 disabled:opacity-50">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 20 20" fill="currentColor"
            x-show="status !== 'loading'">
            <path fill-rule="evenodd"
                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                clip-rule="evenodd" />
        </svg>
        <span x-text="status === 'loading' ? 'Obteniendo ubicación...' : 'Obtener Ubicación GPS'"></span>
    </button>

    <!-- Estados -->
    <template x-if="status === 'loading'">
        <div class="flex items-center p-4 text-blue-800 rounded-lg bg-blue-50">
            <svg class="w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span x-text="message"></span>
        </div>
    </template>

    <template x-if="status === 'success'">
        <div class="p-4 text-green-800 rounded-lg bg-green-50">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
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
        <div class="p-4 text-red-800 rounded-lg bg-red-50">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <span x-text="error"></span>
            </div>
            <button @click="getLocation()"
                class="px-3 py-1 mt-2 text-sm font-medium text-red-700 transition bg-red-100 rounded hover:bg-red-200">
                Reintentar
            </button>
        </div>
    </template>
</div>
