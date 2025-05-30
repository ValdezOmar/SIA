<x-filament::card>
    <div class="">
        {{-- <div class="flex items-center space-x-1">
            <x-heroicon-o-cloud class="h-6 w-6 text-primary-500" />
            <h3 class="text-lg font-small">Carpetas Compartidas</h3>
        </div>
         --}}
        
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <x-filament::button 
                icon="heroicon-o-cloud"
                color="info"
                tag="a"
                href="{{ $this->getNextcloudUrl() }}"
                target="_blank"
            >
                Carpetas Compartidas
            </x-filament::button>
            
            <x-filament::button 
                icon="heroicon-o-arrow-down-tray"
                color="success"
                tag="a"
                href="{{ $this->getClientDownloadUrl() }}"
                target="_blank"
            >
                Descargar Cliente
            </x-filament::button>
        </div>
    </div>
</x-filament::card>