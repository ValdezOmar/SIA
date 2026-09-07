<?php

namespace App\Filament\Resources\Ventas\ClienteResource\Pages;

use App\Filament\Resources\Ventas\ClienteResource;
use App\Services\Ventas\ExportarContactosService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    public function getSubheading(): ?string
    {
        return 'Primero registre al cliente. Después podrá crear sus cotizaciones, pedidos y facturas desde el módulo Ventas.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('descargarContactos')
                ->label('Descargar contactos')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Descargar contactos para móvil')
                ->modalDescription('Los contactos tendrán el formato 26001 JUAN QUISPE. Se exportan los celulares de los clientes del listado filtrado; los números locales usan +591 (Bolivia). Para evitar repetir contactos, adjunte los archivos VCF ya importados en ese teléfono. Importar el mismo archivo varias veces puede crear duplicados.')
                ->form([
                    FileUpload::make('archivos_anteriores')
                        ->label('Archivos VCF ya importados (opcional)')
                        ->multiple()
                        ->maxFiles(20)
                        ->maxSize(5120)
                        ->storeFiles(false)
                        ->helperText('En la primera descarga déjelo vacío. En las siguientes, adjunte todos los archivos anteriores o un VCF exportado desde el teléfono: se excluirán sus números.'),
                ])
                ->modalSubmitActionLabel('Descargar VCF')
                ->action(function (array $data) {
                    $anteriores = '';
                    foreach ($data['archivos_anteriores'] ?? [] as $archivo) {
                        $texto = $archivo->get();
                        if (! str_contains(strtoupper($texto), 'BEGIN:VCARD')) {
                            Notification::make()->title('Adjunte únicamente archivos de contactos VCF.')->danger()->send();

                            return null;
                        }
                        $anteriores .= $texto."\r\n";
                    }
                    $contenido = app(ExportarContactosService::class)->generar(
                        $this->getFilteredTableQuery()->reorder('id')->cursor(),
                        $anteriores,
                    );
                    if ($contenido === '') {
                        Notification::make()->title('No hay contactos nuevos con celular para descargar.')->info()->send();

                        return null;
                    }

                    return response()->streamDownload(
                        fn () => print($contenido),
                        'contactos-clientes-'.now()->format('Y-m-d-His').'.vcf',
                        ['Content-Type' => 'text/vcard; charset=UTF-8'],
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }
}
