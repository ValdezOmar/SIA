<?php

namespace App\Filament\Exports;

use App\Models\Almacen\Inventario;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;


class InventarioExporter extends Exporter
{
    protected static ?string $model = Inventario::class;
    private static array $counter = ['value' => 1];

    public static function getColumns(): array
    {
        self::$counter['value'] = 1;
        return [
            // umeración de filas
            ExportColumn::make('numero_fila')
                ->label('No.')
                ->state(function () {
                    return self::$counter['value']++;
                }),

            ExportColumn::make('codigo')
                ->label('Código'),
            ExportColumn::make('codigo_alterno')
                ->label('Código Alterno'),
            ExportColumn::make('descripcion')
                ->label('Descripción'),
            ExportColumn::make('presentacion')
                ->label('Presentación'),
            ExportColumn::make('unidad')
                ->label('Unidad'),
            ExportColumn::make('cod_almacen')
                ->label('Almacén'),
            ExportColumn::make('nombre_almacen')
                ->label('Nombre Almacén'),
            ExportColumn::make('lote')
                ->label('Lote'),
            ExportColumn::make('fecha_ven')
                ->label('Fecha Vencimiento')
                ->formatStateUsing(fn($state) => $state?->format('d/m/Y')),
            ExportColumn::make('saldo_actual')
                ->label('Saldo Actual'),
            // ExportColumn::make('codigo_correcto')
            //     ->label('Código Correcto'),
            // ExportColumn::make('descripcion_correcto')
            //     ->label('Descripción Correcta'),
            // ExportColumn::make('presentacion_correcto')
            //     ->label('Presentación Correcta'),
            // ExportColumn::make('unidad_correcto')
            //     ->label('Unidad Correcta'),
            // ExportColumn::make('codigo_alterno_correcto')
            //     ->label('Código Alterno Correcto'),
            // ExportColumn::make('cod_almacen_correcto')
            //     ->label('Código Almacén Correcto'),
            // ExportColumn::make('nombre_almacen_correcto')
            //     ->label('Nombre Almacén Correcto'),
            // ExportColumn::make('lote_correcto')
            //     ->label('Lote Correcto'),
            // ExportColumn::make('fecha_ven_correcto')
            //     ->label('Fecha Vencimiento Correcta')
            //     ->formatStateUsing(fn($state) => $state?->format('d/m/Y')),
            ExportColumn::make('saldo_contado')
                ->label('Saldo Contado')
                ->formatStateUsing(fn($state) => $state ?? 'Sin verificar'),

            // Saca las diferencias de conteo
            ExportColumn::make('diferencia_calc')
                ->label('Diferencia')
                ->state(function (Inventario $record): string {
                    if ($record->saldo_contado === null) {
                        return 'Sin verificar';
                    }

                    $diferencia = $record->saldo_actual - $record->saldo_contado;
                    return number_format($diferencia, 2);
                })
                ->formatStateUsing(function (string $state): string {
                    return $state;
                }),

            ExportColumn::make('observacion')
                ->label('Observación')
                ->formatStateUsing(fn($state) => $state ?? 'Ninguno'),
            // ExportColumn::make('fecha_conteo_inventario')
            //     ->label('Fecha Conteo')
            //     ->formatStateUsing(fn($state) => $state->format('d/m/Y') ?? 'Ninguno'),
            ExportColumn::make('usuario')
                ->label('Usuario de Registro')
                ->formatStateUsing(fn($state) => $state ?? 'Ninguno. '),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'La exportación de artículos ha finalizado. ' . number_format($export->successful_rows) . ' ' . str('fila')->plural($export->successful_rows) . ' exportadas.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('fila')->plural($failedRowsCount) . ' no se pudieron exportar.';
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
        return 'reporte_inventario_' . now()->format('Y-m-d_H-i-s');
    }

    // Estilo para las celdas de Excel
    public function getXlsxCellStyle(): ?Style
    {
        return (new Style())
            ->setFontSize(10)
            ->setFontName('Arial')
            ->setCellAlignment(CellAlignment::LEFT)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setBorder($this->getDefaultBorder());
    }

    // Estilo para las cabeceras de Excel
    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style())
            ->setFontSize(11)
            ->setFontName('Arial')
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('2a6099') // Azul corporativo
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setBorder($this->getDefaultBorder());
    }
    // Estilo para las celdas de fechas 
    public function getXlsxCellStyleForDate(): ?Style
    {
        return (new Style())
            ->setFontSize(10)
            ->setFontName('Arial')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setBorder($this->getDefaultBorder());
    }
    // Estilo para las celdas numéricas
    public function getXlsxCellStyleForNumeric(): ?Style
    {
        return (new Style())
            ->setFontSize(10)
            ->setFontName('Arial')
            ->setCellAlignment(CellAlignment::RIGHT)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setBorder($this->getDefaultBorder());
    }

    // Método para crear bordes estándar
    protected function getDefaultBorder(): Border
    {
        return new Border(
            new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::RIGHT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::TOP, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
        );
    }

    // Opcional: Aplicar estilos específicos por tipo de columna
    public function getColumnStyle(ExportColumn $column): ?Style
    {
        // Estilo especial para columnas de fecha
        if (str_contains($column->getName(), 'fecha')) {
            return $this->getXlsxCellStyleForDate();
        }

        // Estilo especial para columnas numéricas
        if (in_array($column->getName(), ['saldo_actual', 'saldo_contado'])) {
            return $this->getXlsxCellStyleForNumeric();
        }

        // Estilo para columnas numéricas
        if (in_array($column->getName(), ['saldo_actual', 'saldo_contado', 'diferencia_calc'])) {
            $style = $this->getXlsxCellStyleForNumeric();

            // Estilo condicional para diferencias
            if ($column->getName() === 'diferencia_calc' && $this->record) {
                if ($this->record->saldo_contado !== null) {
                    $diferencia = $this->record->saldo_actual - $this->record->saldo_contado;
                    if ($diferencia != 0) {
                        $style->setFontColor(Color::RED);
                    }
                }
            }
            return $style;
        }
        //ESTILO DE NUMERO DE FILA
        if ($column->getName() === 'numero_fila') {
            return (new Style())
                ->setFontSize(10)
                ->setFontName('Arial')
                ->setCellAlignment(CellAlignment::CENTER)
                ->setShouldShrinkToFit(true) // Ajusta el texto al ancho
                ->setBorder($this->getDefaultBorder());
        }
        return null; // Usará getXlsxCellStyle() por defecto
    }
}