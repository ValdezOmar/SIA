document.addEventListener('livewire:init', () => {
    Livewire.on('request-gps', () => {
        if (navigator.geolocation) {
            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const coords = `${position.coords.latitude},${position.coords.longitude}`;
                    window.dispatchEvent(new CustomEvent('gps-updated', {
                        detail: { location: coords }
                    }));
                },
                (error) => {
                    let message = 'Error desconocido';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message = "Debes permitir el acceso a la ubicación para registrar asistencias";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message = "La información de ubicación no está disponible";
                            break;
                        case error.TIMEOUT:
                            message = "Tiempo de espera agotado al obtener la ubicación";
                            break;
                    }
                    window.dispatchEvent(new CustomEvent('gps-error', {
                        detail: { message }
                    }));
                },
                options
            );
        } else {
            window.dispatchEvent(new CustomEvent('gps-error', {
                detail: { message: "Tu navegador no soporta geolocalización" }
            }));
        }
    });
});

// Iniciar la solicitud de GPS cuando se carga el modal
document.addEventListener('filament-form-loaded', (e) => {
    if (e.detail.formComponentName === 'create-asistencia') {
        window.dispatchEvent(new Event('request-gps'));
    }
});