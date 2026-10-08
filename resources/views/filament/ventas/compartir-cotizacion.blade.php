@php
    $nombre = 'Proforma-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $cotizacion->codigo).'.pdf';
    $mensaje = 'Le comparto la proforma '.$cotizacion->codigo.'.';
    $telefono = preg_replace('/\D/', '', $cotizacion->cliente?->celular ?? '');
    if (strlen($telefono) === 8) {
        $telefono = '591'.$telefono;
    }
    $telefono = preg_match('/^[1-9][0-9]{7,14}$/', $telefono) ? $telefono : '';
    $whatsapp = 'https://wa.me/'.$telefono.'?text='.rawurlencode($mensaje);
@endphp

<div x-data="{
    archivo: null,
    ocupado: false,
    compatible: false,
    estado: 'Preparando el PDF…',
    async init() {
        try {
            const respuesta = await fetch(@js(route('cotizaciones.pdf', $cotizacion)), { credentials: 'same-origin', headers: { Accept: 'application/pdf' } });
            if (!respuesta.ok || !respuesta.headers.get('content-type')?.includes('application/pdf')) throw new Error();
            const contenido = await respuesta.blob();
            if (contenido.size === 0 || await contenido.slice(0, 5).text() !== '%PDF-') throw new Error();
            this.archivo = new File([contenido], @js($nombre), { type: 'application/pdf' });
            this.compatible = !!(navigator.share &amp;&amp; navigator.canShare &amp;&amp; navigator.canShare({ files: [this.archivo] }));
            this.estado = this.compatible ? 'PDF listo. Pulsa Compartir PDF y selecciona WhatsApp y el destinatario.' : 'Este navegador no permite compartir archivos directamente. Descarga el PDF, abre WhatsApp y adjúntalo en el chat.';
        } catch (error) {
            this.estado = 'No se pudo preparar el PDF. Cierra esta ventana e inténtalo de nuevo.';
        }
    },
    descargar() {
        if (!this.archivo) return;
        const url = URL.createObjectURL(this.archivo);
        const enlace = document.createElement('a');
        enlace.href = url;
        enlace.download = this.archivo.name;
        document.body.appendChild(enlace);
        enlace.click();
        enlace.remove();
        setTimeout(() => URL.revokeObjectURL(url), 60000);
        this.estado = 'Se solicitó la descarga del PDF. Revisa las descargas del navegador y adjunta el archivo en WhatsApp.';
    },
    async compartir() {
        if (!this.archivo || !this.compatible || this.ocupado) return;
        this.ocupado = true;
        try {
            await navigator.share({ files: [this.archivo] });
            this.estado = 'El PDF se entregó al menú de compartir. Comprueba que WhatsApp muestre el documento adjunto antes de enviarlo. La cotización conserva su estado.';
        } catch (error) {
            this.estado = error.name === 'AbortError' ? 'Se canceló el uso del menú de compartir. Puedes intentarlo nuevamente.' : 'No se pudo compartir. Descarga el PDF y adjúntalo en WhatsApp.';
        } finally {
            this.ocupado = false;
        }
    }
}" class="space-y-4">
    <p role="status" aria-live="polite" x-text="estado"></p>
    <p class="text-sm">Para adjuntar el documento, pulsa Compartir PDF y elige WhatsApp en el menú del dispositivo. Antes de enviar, comprueba que aparezca la tarjeta del archivo .pdf.</p>
    <x-filament::button x-show="compatible" x-bind:disabled="!archivo || ocupado" x-on:click="compartir()" icon="heroicon-o-share">
        Compartir PDF
    </x-filament::button>
    <div class="flex flex-wrap gap-3">
        <x-filament::button x-bind:disabled="!archivo" x-on:click="descargar()" color="gray" icon="heroicon-o-arrow-down-tray">
            Descargar PDF
        </x-filament::button>
        <x-filament::button tag="a" :href="$whatsapp" target="_blank" rel="noopener noreferrer" color="success">
            Abrir chat de WhatsApp (sin adjunto)
        </x-filament::button>
    </div>
    <p class="text-sm">Abrir el chat prepara el mensaje, pero requiere adjuntar manualmente el PDF descargado. Para compartir archivos directamente se necesita HTTPS y un navegador compatible.</p>
</div>
