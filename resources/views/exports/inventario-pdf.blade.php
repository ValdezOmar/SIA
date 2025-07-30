<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
            @bottom-right {
                content: counter(page) " de " counter(pages);
                font-size: 6pt;
                color: #666;
            }
        }
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
            padding: 10mm;
            width: 100%;
            height: 30mm;
            text-align: center;
            font-size: 7pt;
        }
        .firma::before {
            content: "Firma de conformidad";
            display: block;
            margin-bottom: 5mm;
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

    <div class="info">
        <strong>Total de registros:</strong> {{ number_format(count($records)) }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                <th>{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($records as $index => $record)
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
                    {{ $value }}
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div>{{ $user }} - {{ now()->format('d/m/Y H:i') }}</div>
        <div class="firma"></div>
    </div>
</body>
</html>
