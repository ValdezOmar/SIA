<div x-data="{
    status: 'idle', error: '', location: null,
    async getLocation() {
        if (!navigator.geolocation) return this.fail('Este navegador no permite consultar la ubicación.');
        this.status = 'loading'; this.error = '';
        try {
            const position = await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }));
            this.location = `${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`;
            this.status = 'success';
            this.$wire.set('localizacion', this.location);
            this.$wire.set('id_equipo', JSON.stringify({ platform: navigator.platform || 'unknown', mobile: /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent), agent: navigator.userAgent.slice(0, 160) }));
        } catch (error) {
            this.fail(({ 1: 'No se concedió permiso para usar la ubicación. Habilítalo en el navegador e inténtalo otra vez.', 2: 'No fue posible determinar la ubicación. Revisa que el GPS y la conexión estén activos.', 3: 'La ubicación tardó demasiado. Muévete a un lugar con mejor señal e inténtalo nuevamente.' })[error.code] || 'No se pudo obtener la ubicación actual.');
        }
    },
    fail(message) { this.status = 'error'; this.error = message; this.location = null; this.$wire.set('localizacion', ''); this.$wire.set('id_equipo', ''); }
}" class="sia-attendance-location">
    <div class="sia-attendance-location__heading">
        <span class="sia-attendance-location__step">1</span>
        <div><h3>Confirma tu ubicación</h3><p>Usamos el GPS de este dispositivo para validar la marcación.</p></div>
    </div>

    <template x-if="status !== 'success'">
        <div class="sia-attendance-location__request" x-transition.opacity>
            <div class="sia-attendance-location__request-icon"><x-filament::icon icon="heroicon-m-map-pin" /></div>
            <div><strong>Marca desde tu ubicación actual</strong><span>Activa la ubicación del dispositivo cuando el navegador lo solicite.</span></div>
            <button type="button" class="sia-attendance-location__button" @click="getLocation()" :disabled="status === 'loading'">
                <svg x-show="status !== 'loading'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm1-12a1 1 0 1 0-2 0v3.586L7.293 11.293a1 1 0 1 0 1.414 1.414L10 11.414l1.293 1.293a1 1 0 0 0 1.414-1.414L11 9.586V6Z" clip-rule="evenodd" /></svg>
                <svg x-show="status === 'loading'" class="sia-attendance-location__spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".3" /><path d="M12 3a9 9 0 0 1 9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                <span x-text="status === 'loading' ? 'Obteniendo ubicación' : 'Usar mi ubicación'"></span>
            </button>
        </div>
    </template>
    <template x-if="status === 'loading'"><p class="sia-attendance-location__status sia-attendance-location__status--loading">Estamos consultando el GPS. Esto puede tardar unos segundos.</p></template>
    <template x-if="status === 'success'">
        <div class="sia-attendance-location__verified" x-transition.opacity>
            <span class="sia-attendance-location__verified-icon"><x-filament::icon icon="heroicon-m-check" /></span>
            <div><strong>Ubicación verificada</strong><span>Coordenadas registradas para esta marcación</span><code x-text="location"></code></div>
        </div>
    </template>
    <template x-if="status === 'error'"><div class="sia-attendance-location__status sia-attendance-location__status--error"><span x-text="error"></span><button type="button" @click="getLocation()">Reintentar</button></div></template>
</div>

<style>
 .sia-attendance-location{display:grid;gap:1rem;padding:.25rem 0}.sia-attendance-location__heading{display:flex;align-items:flex-start;gap:.7rem}.sia-attendance-location__heading h3{margin:.05rem 0 .2rem;color:var(--sia-text);font-size:.98rem;font-weight:750}.sia-attendance-location__heading p{margin:0;color:var(--sia-muted);font-size:.8rem;line-height:1.4}.sia-attendance-location__step{display:grid;place-items:center;flex:0 0 1.65rem;width:1.65rem;height:1.65rem;border-radius:999px;background:color-mix(in srgb,var(--sia-primary) 14%,transparent);color:var(--sia-primary);font-size:.75rem;font-weight:800}.sia-attendance-location__request{display:grid;grid-template-columns:auto minmax(0,1fr);align-items:center;gap:.8rem;padding:1rem;border:1px solid var(--sia-border);border-radius:.85rem;background:var(--sia-surface-subtle)}.sia-attendance-location__request-icon{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:.7rem;background:color-mix(in srgb,var(--sia-primary) 12%,transparent);color:var(--sia-primary)}.sia-attendance-location__request-icon svg{width:1.25rem;height:1.25rem}.sia-attendance-location__request strong,.sia-attendance-location__request span{display:block}.sia-attendance-location__request strong{color:var(--sia-text);font-size:.86rem;font-weight:700}.sia-attendance-location__request span{margin-top:.16rem;color:var(--sia-muted);font-size:.76rem;line-height:1.35}.sia-attendance-location__button{grid-column:2;justify-self:start;display:inline-flex;align-items:center;gap:.45rem;min-height:2.45rem;margin-top:-.25rem;padding:.45rem .8rem;border:0;border-radius:.65rem;background:var(--sia-primary);color:#fff;font-size:.8rem;font-weight:700;box-shadow:0 .25rem .65rem color-mix(in srgb,var(--sia-primary) 23%,transparent);transition:transform .15s ease,filter .15s ease}.sia-attendance-location__button:hover{filter:brightness(1.06);transform:translateY(-1px)}.sia-attendance-location__button:disabled{cursor:wait;opacity:.8;transform:none}.sia-attendance-location__button svg{width:1rem;height:1rem}.sia-attendance-location__spinner{animation:sia-attendance-spin .8s linear infinite}.sia-attendance-location__status{margin:0;padding:.7rem .85rem;border-radius:.65rem;font-size:.78rem;line-height:1.4}.sia-attendance-location__status--loading{background:color-mix(in srgb,#3b82f6 10%,transparent);color:#2563eb}.sia-attendance-location__status--error{display:flex;align-items:center;justify-content:space-between;gap:.75rem;background:#fff1f2;color:#be123c}.sia-attendance-location__status--error button{flex:0 0 auto;border:0;background:transparent;color:inherit;font-size:.78rem;font-weight:750;text-decoration:underline;cursor:pointer}.sia-attendance-location__verified{display:flex;gap:.75rem;padding:1rem;border:1px solid #bbf7d0;border-radius:.85rem;background:#f0fdf4;color:#166534}.sia-attendance-location__verified-icon{display:grid;place-items:center;flex:0 0 2rem;width:2rem;height:2rem;border-radius:999px;background:#16a34a;color:#fff}.sia-attendance-location__verified-icon svg{width:1.15rem;height:1.15rem}.sia-attendance-location__verified strong,.sia-attendance-location__verified span,.sia-attendance-location__verified code{display:block}.sia-attendance-location__verified strong{font-size:.9rem;font-weight:750}.sia-attendance-location__verified span{margin-top:.1rem;font-size:.76rem}.sia-attendance-location__verified code{margin-top:.45rem;color:#15803d;font-size:.77rem;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}.dark .sia-attendance-location__heading h3,.dark .sia-attendance-location__request strong{color:#f8fafc}.dark .sia-attendance-location__request{border-color:var(--sia-dark-border);background:var(--sia-dark-elevated)}.dark .sia-attendance-location__status--loading{color:#93c5fd}.dark .sia-attendance-location__status--error{background:rgb(136 19 55 / .25);color:#fda4af}@keyframes sia-attendance-spin{to{transform:rotate(360deg)}}@media(max-width:640px){.sia-attendance-location__button{grid-column:1/-1;justify-self:stretch;justify-content:center;margin-top:0}}
</style>
