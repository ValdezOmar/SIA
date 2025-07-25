<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 15mm; }
        .header { text-align: center; margin-bottom: 10mm; }
        .header h1 { color: #2a6099; font-size: 16pt; }
        .info { margin-bottom: 5mm; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        th { background: #2a6099; color: white; padding: 3mm; text-align: left; }
        td { padding: 2mm; border: 0.5mm solid #ddd; }
        .footer { margin-top: 10mm; text-align: right; font-size: 8pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div>Generado el: {{ $date }}</div>
    </div>

    <div class="info">
        <strong>Total de registros:</strong> {{ count($records) }}
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
            @foreach($records as $record)
            <tr>
                @foreach($columns as $col)
                <td>{{ $col['format']($record) }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{ $user }} - {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>