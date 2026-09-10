<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="siaBarcodeScanner($wire, @js($getStatePath()))" x-on:keydown.escape.window="close()">
        <div class="flex items-center gap-2">
            <x-filament::input.wrapper class="flex-1" :disabled="$isDisabled()" :valid="! $errors->has($getStatePath())">
                <x-filament::input :id="$getId()" :placeholder="$getPlaceholder()" :disabled="$isDisabled()"
                    :attributes="$applyStateBindingModifiers(\Illuminate\View\ComponentAttributeBag::make(['wire:model' => $getStatePath()]))" type="text" />
            </x-filament::input.wrapper>
            <x-filament::button type="button" icon="heroicon-o-qr-code" x-on:click="open()" :disabled="$isDisabled()">Escanear</x-filament::button>
        </div>
        <div x-show="opened" x-cloak class="mt-3 space-y-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700" role="region" aria-label="Escáner de códigos">
            <label class="block text-sm">Cámara
                <select x-model="deviceId" x-on:change="start()" class="w-full rounded-lg border p-2 dark:bg-gray-900">
                    <template x-for="camera in cameras" :key="camera.deviceId"><option :value="camera.deviceId" x-text="camera.label"></option></template>
                </select>
            </label>
            <div wire:ignore><video x-ref="video" autoplay playsinline muted class="h-64 w-full rounded-lg object-cover"></video></div>
            <p x-show="error" x-text="error" role="alert" class="text-sm text-danger-600"></p>
            <div class="flex gap-2">
                <x-filament::button type="button" color="gray" x-show="canTorch" x-on:click="toggleTorch()">Linterna</x-filament::button>
                <x-filament::button type="button" color="gray" x-on:click="close()">Cerrar cámara</x-filament::button>
            </div>
        </div>
    </div>
</x-dynamic-component>
