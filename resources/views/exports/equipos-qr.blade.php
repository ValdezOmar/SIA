<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>QR Equipos</title>

    <style>
        /* ===============================
           CONFIGURACIÓN BÁSICA
        ================================ */
        @page {
            size: letter;
            margin: 6mm;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        /* ===============================
           CONTENEDOR PRINCIPAL - SIN ALTURA FIJA
        ================================ */
        .page {
            width: 100%;
            page-break-after: always;
        }

        /* ===============================
           TABLA CON 3 COLUMNAS
        ================================ */
        .cards-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .cards-table td {
            width: 33.33%;
            height: 52mm; /* Altura exacta para 5 filas en carta */
            padding: 2mm;
            vertical-align: top;
            page-break-inside: avoid;
        }

        /* ===============================
           TARJETA CON ALTURA FIJA
        ================================ */
        .card {
            width: 100%;
            height: 100%;
            max-height: 48mm; /* Un poco menos que la celda */
            border: 1px solid #e2e8f0;
            padding: 2mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ===============================
           CABECERA DE LA TARJETA
        ================================ */
        .card-header {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            padding: 1.5mm;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            margin-bottom: 1mm;
            flex-shrink: 0;
        }

        /* ===============================
           QR - TAMAÑO CONTROLADO
        ================================ */
        .qr-container {
            text-align: center;
            margin: 0.5mm 0;
            flex-shrink: 0;
        }

        .qr-container img {
            width: 80px;
            height: 80px;
        }

        /* ===============================
           CONTENIDO CON FLEXBOX
        ================================ */
        .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            text-align: center;
        }

        .info-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            text-align: center;
        }

        .info-item {
            font-size: 8px;
            margin-bottom: 0.3mm;
            line-height: 1.1;
            text-align: center;
        }

        .info-label {
            font-weight: bold;
            text-align: center;
        }

        /* ===============================
           LOGO CONTAINER
        ================================ */
        .logo-container {
            text-align: center;
            margin: 1mm 0;
            min-height: 15px;
            flex-shrink: 0;
        }

        .logo-container img {
            max-width: 100px;
            max-height: 50px;
            object-fit: contain;
        }

        /* ===============================
           FOOTER - SIEMPRE ABAJO
        ================================ */
        .card-footer {
            font-size: 8px;
            color: #666;
            text-align: center;
            border-top: 1px dashed #e2e8f0;
            padding-top: 0.5mm;
            margin-top: 0.5mm;
            flex-shrink: 0;
        }

        /* ===============================
           PÁGINA EN BLANCO (PARA CELDAS VACÍAS)
        ================================ */
        .empty-cell {
            width: 100%;
            height: 100%;
        }
    </style>
</head>

<body>

@php
    // Pre-cálculo de URLs para máxima optimización
    $baseUrl = config('app.url', 'http://localhost:8000');
    $logos = [
        1 => $baseUrl . '/storage/logo_novanexa.png',
        2 => $baseUrl . '/storage/logo_ireilab.png',
        3 => $baseUrl . '/storage/logo_requilab.png',
    ];
    
    // Preprocesar equipos con logos
    foreach ($equipos as &$equipo) {
        $equipo['logo_url'] = isset($logos[$equipo['empresa'] ?? 0]) 
            ? $logos[$equipo['empresa']] 
            : null;
    }
    unset($equipo); // Romper referencia
    
    $cardsPerPage = 12;
    $pages = array_chunk($equipos, $cardsPerPage);
@endphp

@foreach($pages as $page)
    <div class="page">
        <table class="cards-table">
            @foreach(array_chunk($page, 3) as $row)
                <tr>
                    @foreach($row as $equipo)
                        <td>
                            <div class="card">
                                <div class="card-header">
                                    SOLICITUD DE SOPORTE TÉCNICO<br>
                                    COD: {{ $equipo['codigo'] }}
                                </div>
                                
                                <div class="qr-container">
                                    <img src="{{ $equipo['qr_code'] }}" alt="QR">
                                </div>
                                
                                <div class="card-content">
                                    <div class="info-container">
                                        <div class="info-item">
                                            <span class="info-label">MARCA:</span> {{ $equipo['marca'] }}
                                        </div>
                                        
                                        <div class="info-item">
                                            <span class="info-label">MODELO:</span> {{ $equipo['modelo'] }}
                                        </div>
                                    </div>
                                    
                                    {{-- LOGO - SIN SWITCH, SIN FILE_EXISTS --}}
                                    <div class="logo-container">
                                        @if(!empty($equipo['logo_url']))
                                            <img src="{{ $equipo['logo_url'] }}" 
                                                 alt="Logo"
                                                 style="max-width: 60px; max-height: 20px;">
                                        @else
                                            <div style="font-size: 7px; color: #666;">
                                                {{ $equipo['cliente'] ?? '' }}
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="card-footer">
                                        ESCANEAR CÓDIGO QR PARA VER DETALLES COMPLETOS
                                    </div>
                                </div>
                            </div>
                        </td>
                    @endforeach
                    
                    @for($i = count($row); $i < 3; $i++)
                        <td><div class="empty-cell"></div></td>
                    @endfor
                </tr>
            @endforeach
            
            @php
                $emptyRows = 4 - ceil(count($page) / 3);
            @endphp
            
            @for($i = 0; $i < $emptyRows; $i++)
                <tr>
                    @for($j = 0; $j < 3; $j++)
                        <td><div class="empty-cell"></div></td>
                    @endfor
                </tr>
            @endfor
        </table>
    </div>
@endforeach

</body>
</html>