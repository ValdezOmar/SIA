<x-filament::card>
    <div class="flex flex-col gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                Correo empresarial
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Acceda al correo institucional de la empresa.
            </p>
        </div>

        <x-filament::button
            icon="heroicon-o-envelope"
            color="primary"
            tag="a"
            href="{{ $this->getMailUrl() }}"
            target="_blank"
            rel="noopener noreferrer"
        >
            Abrir correo empresarial
        </x-filament::button>
    </div>
</x-filament::card>
