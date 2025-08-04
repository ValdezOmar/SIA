<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        /* Estilos optimizados */
        body {
            font-family: Arial, sans-serif;
            font-size: 6pt;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 2mm;
        }
        .header h1 {
            color: #2a6099;
            font-size: 14pt;
            margin: 0;
        }
        .header div {
            font-size: 8pt;
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6pt;
        }
        th {
            background: #2a6099;
            color: white;
            padding: 1mm;
            text-align: center;
            font-weight: bold;
        }
        td {
            padding: 1mm;
            border: 0.2mm solid #ddd;
            text-align: left;
        }
        .footer {
            margin-top: 5mm;
            font-size: 8pt;
            color: #666;
        }
        .firma {
            margin-top: 15mm;
            border: 0.2mm solid #999;
            padding: 5mm;
            width: 90%;
            height: 30mm;
            text-align: center;
            font-size: 7pt;
        }
        .firma::before {
            content: "Sellos y firmas de conformidad de los almaceneros, administradores regionales y veedores";
            display: block;
            margin-bottom: 1mm;
            font-weight: bold;
        }
        /* Colores para diferencias */
        .diferencia-negativa, .faltante, .ajuste-negativo, .stock-movido {
            color: #dc2626;
            font-weight: bold;
        }
        .ajuste-positivo, .sobrante {
            color: #16a34a;
            font-weight: bold;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .stats-table {
            width: 100%; 
            margin-bottom: 10px; 
            border-collapse: collapse;
        }
        .stats-cell {
            width: 33.33%; 
            vertical-align: top; 
            padding: 5px;
        }
    </style>    
</head>
<body>
    <!-- Encabezado del documento -->
    <div class="header">
        <h1>{{ $title }} - {{ $records->pluck('empresa')->unique()->implode(', ') }}</h1>
        <div class="nowrap">Generado el: {{ $date }}</div>
        <div class="nowrap">Generado por: {{ $user }}</div>
    </div>

    <!-- Tabla de estadísticas resumidas -->
    <table class="stats-table">
        @php
            // Cálculo de estadísticas básicas
            $totalRegistros = count($records);
            $totalContados = $records->whereNotNull('saldo_contado')->count();
            $tasaContados = $totalRegistros > 0 ? round(($totalContados / $totalRegistros) * 100, 2) : 0;
            
            // Filtrado de registros con diferencias
            $registrosConDiferencia = $records->filter(function($record) {
                return $record->saldo_contado !== null && $record->saldo_contado != $record->saldo_actual;
            });
            
            $totalDiferencias = $registrosConDiferencia->count();
            
            // Cálculo de sobrantes y faltantes
            $sobrantes = $registrosConDiferencia->filter(function($record) {
                return $record->saldo_contado > $record->saldo_actual;
            });
            
            $faltantes = $registrosConDiferencia->filter(function($record) {
                return $record->saldo_contado < $record->saldo_actual;
            });
            
            $sumaSobrantes = $sobrantes->sum(function($record) {
                return $record->saldo_contado - $record->saldo_actual;
            });
            
            $sumaFaltantes = $faltantes->sum(function($record) {
                return $record->saldo_actual - $record->saldo_contado;
            });
        @endphp
        <tr>
            <td class="stats-cell">
                <strong>Total registros:</strong> {{ number_format($totalRegistros) }}<br>
                <strong>Total contados:</strong> {{ number_format($totalContados) }}<br>
                <strong>Tasa contados:</strong> {{ $tasaContados }}%
            </td>
            <td class="stats-cell">
                <strong>Con diferencia:</strong> {{ number_format($totalDiferencias) }}<br>
                <strong>Sobrantes:</strong> <span class="sobrante">+{{ number_format($sobrantes->count()) }}</span><br>
                <strong>Faltantes:</strong> <span class="faltante">-{{ number_format($faltantes->count()) }}</span>
            </td>
            <td class="stats-cell">
                <strong>Total sobrantes:</strong> <span class="sobrante">+{{ number_format($sumaSobrantes, 2) }}</span><br>
                <strong>Total faltantes:</strong> <span class="faltante">-{{ number_format($sumaFaltantes, 2) }}</span><br>
            </td>
        </tr>
    </table>

    <!-- Tabla principal de datos detallados -->
    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                <th>{{ $col['label'] }}</th>
                @endforeach
                <th>Saldo Actual</th>
                <th>Saldo ajustado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $index => $record)
            @php
                // Obtener artículo relacionado
                $articulo = App\Models\Almacen\Articulo::firstWhere([
                    'codigo' => $record->codigo,
                    'cod_almacen' => $record->cod_almacen,
                    'lote' => $record->lote
                ]);
                
                $saldoArticulo = $articulo->saldo_actual ?? null;
                $diferencia = null;
                $ajuste = null;
                $textoAjuste = 'N/A';
                $claseAjuste = '';
                
                // Verificar si hay valores para calcular
                if ($record->saldo_contado !== null && $record->saldo_actual !== null) {
                    $diferencia = $record->saldo_contado - $record->saldo_actual;
                    
                    // Caso 1: Cuando no hay diferencia
                    if (abs($diferencia) < 0.0001) {
                        $textoAjuste = 'Sin Novedad';
                        $claseAjuste = 'sin-novedad';
                    }
                    // Caso 2: Cuando hay diferencia y existe saldo del artículo
                    elseif (abs($diferencia) > 0.0001 && $saldoArticulo !== null) {
                        // Subcaso 2.1: Stock movido (saldo artículo es menor que ambos)
                        if ($saldoArticulo < $record->saldo_contado && $saldoArticulo < $record->saldo_actual) {
                            $textoAjuste = 'Stock movido';
                            $claseAjuste = 'stock-movido';
                        } 
                        // Subcaso 2.2: Cálculo normal de ajustes
                        else {
                            if (abs($record->saldo_actual - $saldoArticulo) < 0.0001) {
                                $ajuste = $record->saldo_contado - $saldoArticulo;
                            } else {
                                $ajuste = $saldoArticulo - $record->saldo_actual;
                            }
                            
                            if (abs($ajuste) < 0.0001) {
                                $textoAjuste = 'Ajustado';
                                $claseAjuste = 'sin-novedad';
                            } else {
                                $textoAjuste = number_format($ajuste, 2);
                                $claseAjuste = $ajuste > 0 ? 'ajuste-positivo' : 'ajuste-negativo';
                            }
                        }
                    }
                }
            @endphp
            <tr>
                @foreach($columns as $col)
                @php
                    $value = $col['format']($record, $index);
                    $isNumeric = in_array($col['name'], ['saldo_actual', 'saldo_contado', 'diferencia_calc']);
                    $isDate = $col['name'] === 'fecha_ven';
                    $isNumber = $col['name'] === 'numero_fila';
                @endphp
                <td class="@if($isNumeric) text-right @endif @if($isDate || $isNumber) text-center @endif">
                    {!! $value !!}
                </td>
                @endforeach
                <td class="text-right">
                    {{ $saldoArticulo !== null ? number_format($saldoArticulo, 2) : 'N/A' }}
                </td>
                <td class="text-center {{ $claseAjuste }}">
                    {{ $textoAjuste }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pie de página -->
    <div class="footer">
        <div style="text-align: right; font-size: 6pt; color: #666;">
            Página <span class="page-number"></span> de <span class="page-count"></span>
        </div>
        <div>Sistema SIA - {{ now()->format('d/m/Y H:i') }}</div>
        <div class="firma"></div>
    </div>

    <!-- Script para numeración de páginas -->
    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script('
                if ($PAGE_COUNT > 1) {
                    $font = $fontMetrics->get_font("Arial", "normal");
                    $text = "Página $PAGE_NUM de $PAGE_COUNT";
                    $pdf->text($pdf->get_width() - $fontMetrics->get_text_width($text, $font, 6) - 20, 
                              $pdf->get_height() - 15, 
                              $text, $font, 6);
                }
            ');
        }
    </script>   
</body>
</html>