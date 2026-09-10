import { BrowserMultiFormatReader, BrowserCodeReader } from '@zxing/browser';

// Each field owns its reader, including fields mounted inside Livewire actions.
window.siaBarcodeScanner = (wire, statePath) => ({
    opened: false, cameras: [], deviceId: '', error: '', controls: null,
    reader: null, canTorch: false, torch: false, generation: 0,

    async open() {
        this.opened = true;
        this.error = '';
        await this.$nextTick();
        if (!this.opened) return;
        await this.start();
    },

    async start() {
        this.stop();
        const generation = this.generation;
        this.error = '';
        try {
            this.reader = new BrowserMultiFormatReader();
            const controls = await this.reader.decodeFromConstraints({
                video: this.deviceId ? { deviceId: { exact: this.deviceId } } : { facingMode: 'environment' },
                audio: false,
            }, this.$refs.video, (result) => {
                if (!result || !this.opened || generation !== this.generation) return;
                wire.$set(statePath, result.getText());
                this.beep();
                this.close();
            });
            if (!this.opened || generation !== this.generation) {
                controls.stop();
                return;
            }
            this.controls = controls;
            this.cameras = (await BrowserCodeReader.listVideoInputDevices()).map((camera, i) => ({
                deviceId: camera.deviceId, label: camera.label || `Cámara ${i + 1}`,
            }));
            const track = this.$refs.video.srcObject?.getVideoTracks()[0];
            this.deviceId = track?.getSettings().deviceId || this.deviceId;
            this.canTorch = Boolean(track?.getCapabilities?.().torch);
        } catch (error) {
            if (generation !== this.generation) return;
            this.stop();
            this.error = 'No se pudo abrir la cámara. Revise el permiso del navegador o ingrese el código con el teclado o lector USB.';
        }
    },

    async toggleTorch() {
        const track = this.$refs.video.srcObject?.getVideoTracks()[0];
        try {
            await track?.applyConstraints({ advanced: [{ torch: !this.torch }] });
            this.torch = !this.torch;
        } catch { this.error = 'Esta cámara no permite controlar la linterna.'; }
    },

    beep() {
        const Audio = window.AudioContext || window.webkitAudioContext;
        if (!Audio) return;
        try {
            const audio = new Audio();
            const oscillator = audio.createOscillator();
            oscillator.connect(audio.destination);
            oscillator.frequency.value = 880;
            oscillator.start();
            oscillator.stop(audio.currentTime + 0.1);
            oscillator.onended = () => audio.close();
        } catch { /* Sound is optional. */ }
    },

    stop() {
        this.generation++;
        this.controls?.stop();
        this.controls = null;
        this.$refs.video?.srcObject?.getTracks().forEach(track => track.stop());
        this.canTorch = false;
        this.torch = false;
    },

    close() { this.opened = false; this.stop(); },
    destroy() { this.close(); },
});
