<script src="https://unpkg.com/@zxing/library@latest"></script>
<audio id="beep-sound" src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg" preload="auto"></audio>

<div>
    <label for="{{ $getId() }}" class="text-sm font-medium text-gray-950 dark:text-white">
        {{ $getLabel() ?? 'Escanea el código' }}
    </label>

    <!-- Input + Botón escáner -->
    <div class="flex gap-2 items-center mt-1">
        <x-filament::input
            type="text"
            name="{{ $getName() }}"
            id="{{ $getId() }}"
            value="{{ $getState() }}"
            placeholder="{{ $getPlaceholder() }}"
            class="w-full dark:bg-gray-800 dark:text-white dark:border-gray-700 border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 rounded-lg shadow-sm"
        />

        <button type="button"
            onclick="openScannerModal('{{ $getId() }}')"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition"
            title="Escanear Código">
            <!-- Icono Heroicon QR Code -->
            <x-heroicon-o-qr-code class="w-5 h-5" />
        </button>
    </div>

    <!-- Modal QR Scanner -->
    <x-filament::modal id="barcode-scanner-modal" max-width="3xl">
        <x-slot name="header">Escáner de Código</x-slot>

        <div class="p-4 space-y-4">
            <div class="flex items-center gap-2">
                <label for="camera-select" class="text-sm font-medium text-gray-700 dark:text-gray-200">Selecciona Cámara:</label>
                <select id="camera-select"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 px-3 py-1 text-sm">
                    <option>Cargando...</option>
                </select>
                <button id="toggle-torch"
                    class="ml-auto text-sm px-3 py-1 rounded bg-yellow-400 hover:bg-yellow-500 text-black hidden shadow">
                    🔦 Linterna
                </button>
            </div>

            <div id="scanner-container" class="relative w-full rounded overflow-hidden shadow-md">
                <video id="scanner" autoplay playsinline class="w-full h-64 object-cover rounded"></video>
                <div class="overlay absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="scan-area pointer-events-none">
                        <p class="text-white text-sm text-center mt-2 animate-pulse">Busca un Código QR o de Barras</p>
                        <div class="scan-line"></div>
                        <div class="corner top-left"></div>
                        <div class="corner top-right"></div>
                        <div class="corner bottom-left"></div>
                        <div class="corner bottom-right"></div>
                    </div>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <div class="w-full flex justify-center">
                <x-filament::button onclick="closeScannerModal()" color="danger">Cerrar</x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const codeReader = new ZXing.BrowserMultiFormatReader();
        let selectedDeviceId = null;
        let scannerInputId = null;
        let trackRef = null;
        let torchOn = false;

        function listCameras() {
            codeReader.getVideoInputDevices().then(devices => {
                const select = document.getElementById('camera-select');
                select.innerHTML = '';
                devices.forEach((device, index) => {
                    const option = document.createElement('option');
                    option.value = device.deviceId;
                    option.text = device.label || `Cámara ${index + 1}`;
                    select.appendChild(option);
                });
                if (devices.length > 0) {
                    selectedDeviceId = devices[0].deviceId;
                    select.value = selectedDeviceId;
                    startScanner(selectedDeviceId);
                }
                select.addEventListener('change', () => {
                    selectedDeviceId = select.value;
                    stopScanner();
                    startScanner(selectedDeviceId);
                });
            });
        }

        function startScanner(deviceId) {
            codeReader.decodeFromVideoDevice(deviceId, 'scanner', (result, err) => {
                if (result) {
                    const input = document.getElementById(scannerInputId);
                    input.value = result.getText();
                    document.getElementById('beep-sound').play();
                    stopScanner();  // Evita múltiples lecturas
                    closeScannerModal();
                }
            }).then(stream => {
                const video = document.getElementById('scanner');
                if (video.srcObject) {
                    const [track] = video.srcObject.getVideoTracks();
                    trackRef = track;
                    if ('torch' in track.getSettings()) {
                        document.getElementById('toggle-torch').classList.remove('hidden');
                    }
                }
            }).catch(console.error);
        }

        function stopScanner() {
            codeReader.reset();
            const video = document.getElementById('scanner');
            if (video && video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
                video.srcObject = null;
            }
            document.getElementById('toggle-torch').classList.add('hidden');
        }

        window.openScannerModal = function (inputId) {
            scannerInputId = inputId;
            window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'barcode-scanner-modal' } }));
        };

        window.closeScannerModal = function () {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'barcode-scanner-modal' } }));
            stopScanner();
        };

        document.getElementById('toggle-torch').addEventListener('click', () => {
            if (!trackRef) return;
            const capabilities = trackRef.getCapabilities();
            if (capabilities.torch) {
                torchOn = !torchOn;
                trackRef.applyConstraints({ advanced: [{ torch: torchOn }] });
            }
        });

        window.addEventListener('open-modal', (event) => {
            if (event.detail.id === 'barcode-scanner-modal') {
                listCameras();
            }
        });

        window.addEventListener('close-modal', (event) => {
            if (event.detail.id === 'barcode-scanner-modal') {
                stopScanner();
            }
        });
    });
</script>

<style>
    .scan-area {
        position: relative;
        width: 80%;
        height: 80%;
        border-radius: 8px;
    }

    .scan-line {
        position: absolute;
        width: 100%;
        height: 3px;
        background: rgba(255, 0, 0, 0.8);
        animation: scan-line 2s linear infinite;
    }

    @keyframes scan-line {
        0% {
            top: 10%;
        }
        50% {
            top: 90%;
        }
        100% {
            top: 10%;
        }
    }

    .corner {
        width: 20px;
        height: 20px;
        border: 3px solid #3b82f6;
        position: absolute;
    }

    .top-left {
        top: 0;
        left: 0;
        border-right: none;
        border-bottom: none;
    }

    .top-right {
        top: 0;
        right: 0;
        border-left: none;
        border-bottom: none;
    }

    .bottom-left {
        bottom: 0;
        left: 0;
        border-right: none;
        border-top: none;
    }

    .bottom-right {
        bottom: 0;
        right: 0;
        border-left: none;
        border-top: none;
    }
</style>
