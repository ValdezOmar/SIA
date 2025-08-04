<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
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
        .info {
            margin-bottom: 3mm;
            font-size: 8pt;
            border: 0.5mm solid #eee;
            padding: 2mm;
            border-radius: 2mm;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6pt;
            table-layout: auto;
        }
        th {
            background: #2a6099;
            color: white;
            padding: 1mm;
            text-align: center;
            font-weight: bold;
            word-wrap: break-word;
            max-width: 120px;
        }
        td {
            padding: 1mm;
            border: 0.2mm solid #ddd;
            text-align: left;
            word-wrap: break-word;
            max-width: 120px;
            overflow-wrap: break-word;
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
        .diferencia-negativa {
            color: #dc2626;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .ajuste-positivo {
            color: #16a34a;
            font-weight: bold;
        }        
        .ajuste-negativo {
            color: #dc2626;
            font-weight: bold;
        }
        .sobrante {
            color: #16a34a;
        }
        .faltante {
            color: #dc2626;
        }
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
    <div class="header">
        <h1>{{ $title }} @php
            $empresas = $records->pluck('empresa')->unique()->implode(', ');
        @endphp - {{ $empresas }}</h1>
        <div class="nowrap">Generado el: {{ $date }}</div>
        <div class="nowrap">Generado por: {{ $user }}</div>
    </div>

    <table class="stats-table">
        @php
            // Estadísticas existentes
            $totalRegistros = count($records);
            $totalContados = $records->whereNotNull('saldo_contado')->count();
            $tasaContados = $totalRegistros > 0 ? round(($totalContados / $totalRegistros) * 100, 2) : 0;
            
            // Estadísticas de diferencias
            $totalDiferencias = $records->filter(function($record) {
                return $record->saldo_contado !== null && 
                       $record->saldo_contado != $record->saldo_actual;
            })->count();
            
            $totalAjustados = $records->filter(function($record) {
                return $record->saldo_contado !== null && 
                       $record->saldo_contado != 0 && 
                       $record->saldo_actual != 0 &&
                       $record->saldo_contado != $record->saldo_actual;
            })->count();
            
            $tasaAjuste = $totalDiferencias > 0 ? round(($totalAjustados / $totalDiferencias) * 100, 2) : 0;
            
            // Cálculo de sobrantes y faltantes
            $sobrantes = $records->filter(function($record) {
                return $record->saldo_contado !== null && 
                       $record->saldo_contado > $record->saldo_actual;
            });
            
            $faltantes = $records->filter(function($record) {
                return $record->saldo_contado !== null && 
                       $record->saldo_contado < $record->saldo_actual;
            });
            
            $totalSobrantes = $sobrantes->count();
            $totalFaltantes = $faltantes->count();
            
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
                <strong>Tasa de contados:</strong> {{ $tasaContados }}%
            </td>
            <td class="stats-cell">
                <strong>Total diferencia:</strong> {{ number_format($totalDiferencias) }}<br>
                <strong>Total ajustados:</strong> {{ number_format($totalAjustados) }}<br>
                <strong>Tasa de ajuste:</strong> {{ $tasaAjuste }}%
            </td>
            <td class="stats-cell">
                <strong>Sobrantes (Total):</strong> <span class="sobrante">+{{ number_format($sumaSobrantes, 2) }}</span><br>
                <strong>Faltantes (Total):</strong> <span class="faltante">-{{ number_format($sumaFaltantes, 2) }}</span><br>              
            </td>
        </tr>
    </table>

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
                // Obtener el artículo relacionado para cada registro
                $articulo = App\Models\Almacen\Articulo::where('codigo', $record->codigo)
                    ->where('cod_almacen', $record->cod_almacen)
                    ->where('lote', $record->lote)
                    ->first();
                
                // Calcular valores de ajuste
                $saldoArticulo = $articulo ? $articulo->saldo_actual : null;
                $diferencia = ($record->saldo_contado !== null && $record->saldo_actual !== null) 
                    ? ($record->saldo_contado - $record->saldo_actual) 
                    : null;
                $ajuste = ($saldoArticulo !== null && $diferencia !== null) 
                    ? ($saldoArticulo - $record->saldo_actual) 
                    : null;
            @endphp
            <tr>
                @foreach($columns as $col)
                @php
                    $value = $col['format']($record, $index);
                    $isDiferencia = $col['name'] === 'diferencia_calc';
                    $isNumeric = in_array($col['name'], ['saldo_actual', 'saldo_contado', 'diferencia_calc']);
                    $isDate = $col['name'] === 'fecha_ven';
                    $isNumber = $col['name'] === 'numero_fila';
                @endphp
                <td class="
                    @if($isNumeric) text-right @endif
                    @if($isDate || $isNumber) text-center @endif
                    @if($isDiferencia && $value !== 'Sin verificar' && floatval($value) != 0) diferencia-negativa @endif
                ">
                    {!! $value !!}
                </td>
                @endforeach
                <td class="text-right">
                    {{ $saldoArticulo !== null ? number_format($saldoArticulo, 2) : 'N/A' }}
                </td>
                <td class="text-right @if($ajuste !== null) @if($ajuste > 0) ajuste-positivo @elseif($ajuste < 0) ajuste-negativo @endif @endif">
                    {{ $ajuste !== null ? number_format($ajuste, 2) : 'N/A' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div style="text-align: right; font-size: 6pt; color: #666;">
            Página <span class="page-number"></span> de <span class="page-count"></span>
        </div>
        <div>Sistema SIA - {{ now()->format('d/m/Y H:i') }}</div>
        <div class="firma"></div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script('
                if ($PAGE_COUNT > 1) {
                    $currentPage = $PAGE_NUM;
                    $totalPages = $PAGE_COUNT;
                    
                    $font = $fontMetrics->get_font("Arial", "normal");
                    $pageText = "Página $currentPage de $totalPages";
                    $width = $fontMetrics->get_text_width($pageText, $font, 6);
                    $x = $pdf->get_width() - $width - 20;
                    $y = $pdf->get_height() - 15;
                    $pdf->text($x, $y, $pageText, $font, 6);
                }
            ');
        }
    </script>   
</body>
</html>