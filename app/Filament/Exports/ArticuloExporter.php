<?php

namespace App\Filament\Exports;

use App\Models\Almacen\Articulo;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ArticuloExporter extends Exporter
{
    protected static ?string $model = Articulo::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('codigo')
                ->label('Código Artículo'),
                
            ExportColumn::make('descripcion')
                ->label('Descripción'),
                
            ExportColumn::make('presentacion')
                ->label('Presentación'),
                
            ExportColumn::make('unidad')
                ->label('Unidad de Medida'),
                
            ExportColumn::make('codigo_alterno')
                ->label('Código Alterno'),
                
            ExportColumn::make('proveedor')
                ->label('Proveedor'),
                
            ExportColumn::make('cod_almacen')
                ->label('Código Almacén'),
                
            ExportColumn::make('nombre_almacen')
                ->label('Nombre Almacén'),
                
            ExportColumn::make('lote')
                ->label('Número de Lote'),
                
            ExportColumn::make('fecha_ven')
                ->label('Fecha Vencimiento')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : 'Sin fecha'),
                
            ExportColumn::make('saldo_actual')
                ->label('Stock Disponible'),
                
            ExportColumn::make('empresa')
                ->label('Empresa'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'La exportación de artículos ha finalizado. ' . number_format($export->successful_rows) . ' ' . str('registro')->plural($export->successful_rows) . ' exportados.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('registro')->plural($failedRowsCount) . ' fallaron al exportar.';
        }

        return $body;
    }

    // Configurar para exportar solo a XLSX (Excel)
    public function getFormats(): array
    {
        return [
            \Filament\Actions\Exports\Enums\ExportFormat::Xlsx,
        ];
    }

    // Configurar el nombre del archivo
    public function getFileName(Export $export): string
    {
        return 'reporte_articulos_' . now()->format('Y-m-d_H-i-s');
    }

    // Estilo para las celdas de Excel
    public function getXlsxCellStyle(): ?\OpenSpout\Common\Entity\Style\Style
    {
        return (new \OpenSpout\Common\Entity\Style\Style())
            ->setFontSize(11)
            ->setFontName('Arial');
    }

    // Estilo para las cabeceras de Excel
    public function getXlsxHeaderCellStyle(): ?\OpenSpout\Common\Entity\Style\Style
    {
        return (new \OpenSpout\Common\Entity\Style\Style())
            ->setFontSize(11)
            ->setFontName('Arial')
            ->setFontBold()
            ->setBackgroundColor('2a6099')
            ->setFontColor('ffffff');
    }
}