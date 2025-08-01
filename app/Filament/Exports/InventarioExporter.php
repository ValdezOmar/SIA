<?php

namespace App\Filament\Exports;

use App\Models\Almacen\Inventario;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\Enums\ExportFormat;
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
            ExportColumn::make('numero_fila')
                ->label('No.')
                ->state(fn() => self::$counter['value']++),

            ExportColumn::make('codigo')->label('Código'),
            ExportColumn::make('codigo_alterno')->label('Código Alterno'),
            ExportColumn::make('descripcion')->label('Descripción'),
            ExportColumn::make('presentacion')->label('Presentación'),
            ExportColumn::make('unidad')->label('Unidad'),
            ExportColumn::make('cod_almacen')->label('Almacén'),
            ExportColumn::make('nombre_almacen')->label('Nombre Almacén'),
            ExportColumn::make('lote')->label('Lote'),
            ExportColumn::make('fecha_ven')
                ->label('Fecha Vencimiento')
                ->formatStateUsing(fn($state) => $state?->format('d/m/Y')),

            ExportColumn::make('saldo_actual')->label('Saldo Actual'),

            ExportColumn::make('saldo_contado')
                ->label('Saldo Contado')
                ->formatStateUsing(fn($state) => $state ?? 'Sin verificar'),

            ExportColumn::make('diferencia_calc')
                ->label('Diferencia')
                ->state(function (Inventario $record): string {
                    if ($record->saldo_contado === null) return 'Sin verificar';

                    $diferencia = $record->saldo_contado - $record->saldo_actual;

                    if ($diferencia < 0) return '' . number_format($diferencia, 2);
                    if ($diferencia > 0) return '' . number_format($diferencia, 2);
                    return number_format($diferencia, 2);
                }),

            ExportColumn::make('observacion')
                ->label('Observación')
                ->formatStateUsing(function (Inventario $record): string {
                    $comentarios = [];

                    if ($record->observacion) {
                        $comentarios[] = $record->observacion;
                    }

                    if ($record->codigo_correcto) {
                        $comentarios[] = "Código correcto: {$record->codigo_correcto}";
                    }

                    if ($record->descripcion_correcto) {
                        $comentarios[] = "Descripción correcta: {$record->descripcion_correcto}";
                    }

                    if ($record->presentacion_correcto) {
                        $comentarios[] = "Presentación correcto: {$record->presentacion_correcto}";
                    }

                    if ($record->unidad_correcto) {
                        $comentarios[] = "Unidad correcta: {$record->unidad_correcto}";
                    }

                    if ($record->codigo_alterno_correcto) {
                        $comentarios[] = "Código alterno correcto: {$record->codigo_alterno_correcto}";
                    }

                    if (!is_null($record->cod_almacen_correcto)) {
                        $comentarios[] = "Cod Almacén correcto: {$record->cod_almacen_correcto}";
                    }

                    if ($record->nombre_almacen_correcto) {
                        $comentarios[] = "Nombre almacén correcto: {$record->nombre_almacen_correcto}";
                    }

                    if ($record->lote_correcto) {
                        $comentarios[] = "Lote correcto: {$record->lote_correcto}";
                    }

                    if ($record->fecha_ven_correcto) {
                        $comentarios[] = "Vencimiento correcto: " . $record->fecha_ven_correcto->format('d/m/Y');
                    }

                    // if ($record->sn_qr_correcto) {
                    //     $comentarios[] = "QR: {$record->sn_qr_correcto}";
                    // }

                    if ($record->empresa_correcto) {
                        $comentarios[] = "Empresa: {$record->empresa_correcto}";
                    }

                    return implode("\n", $comentarios) ?: 'Ninguno';
                }),

            ExportColumn::make('usuario')
                ->label('Usuario de Registro')
                ->formatStateUsing(fn($state) => $state ?? 'Ninguno.'),
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

    public function getFormats(): array
    {
        return [ExportFormat::Xlsx];
    }

    public function getFileName(Export $export): string
    {
        return 'reporte_inventario_' . now()->format('Y-m-d_H-i-s');
    }

    public function getXlsxCellStyle(): ?Style
    {
        return (new Style())
            ->setFontSize(10)
            ->setFontName('Arial')
            ->setCellAlignment(CellAlignment::LEFT)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setBorder($this->getDefaultBorder());
    }

    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style())
            ->setFontSize(11)
            ->setFontName('Arial')
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('2a6099')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setBorder($this->getDefaultBorder());
    }

    public function getColumnStyle(ExportColumn $column): ?Style
    {
        $name = $column->getName();

        // Fecha
        if (str_contains($name, 'fecha')) {
            return (new Style())
                ->setFontSize(10)
                ->setFontName('Arial')
                ->setCellAlignment(CellAlignment::CENTER)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setBorder($this->getDefaultBorder());
        }

        // Numérico
        if (in_array($name, ['saldo_actual', 'saldo_contado', 'diferencia_calc'])) {
            return (new Style())
                ->setFontSize(10)
                ->setFontName('Arial')
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setBorder($this->getDefaultBorder());
        }

        // Número fila
        if ($name === 'numero_fila') {
            return (new Style())
                ->setFontSize(10)
                ->setFontName('Arial')
                ->setCellAlignment(CellAlignment::CENTER)
                ->setShouldShrinkToFit(true)
                ->setBorder($this->getDefaultBorder());
        }

        return null;
    }

    protected function getDefaultBorder(): Border
    {
        return new Border(
            new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::RIGHT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::TOP, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
        );
    }
}