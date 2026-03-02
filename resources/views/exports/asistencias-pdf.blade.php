<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencias</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .reporte-container {
            width: 100%;
        }
        /* Forzar renderizado correcto de caracteres especiales */
        * {
            font-family: 'DejaVu Sans', Arial, sans-serif;
        }
        .header-info {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .header-info h1 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #333;
        }
        .header-info p {
            margin: 5px 0;
            font-size: 11px;
        }
        .empleado-container {
            page-break-after: always;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .empleado-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            table-layout: fixed;
        }
        .empleado-table th, .empleado-table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
            word-wrap: break-word;
        }
        .empleado-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .empleado-header {
            background-color: #e6e6e6;
            padding: 8px;
            margin: 8px 0;
            border-radius: 3px;
            font-size: 11px;
        }
        .weekend {
            background-color: #f9f9f9;
        }
        .omision {
            color: #fd7e14;
            font-weight: bold;
        }
        .retraso {
            color: #dc3545;
            font-weight: bold;
        }
        .registro-remoto {
            color: #0d6efd;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .no-break {
            page-break-inside: avoid;
        }
        .tiempo-retraso {
            font-family: 'DejaVu Sans', monospace;
            white-space: nowrap;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge-info {
            background-color: #17a2b8;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
        }
        .enlace-ubicacion {
            color: #0d6efd;
            text-decoration: none;
            border-bottom: 1px dotted #0d6efd;
            cursor: pointer;
        }
        .enlace-ubicacion:hover {
            color: #084298;
            border-bottom: 1px solid #084298;
        }
        .marcacion-container {
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }
        .marcacion-remota {
            background-color: #e7f1ff;
            border-radius: 3px;
            padding: 1px 3px;
        }
        /* Clase para separadores con entidades HTML */
        .separator {
            font-family: 'DejaVu Sans', monospace;
            margin: 0 2px;
            display: inline-block;
        }
        /* Estilo para horas completas */
        .hora-completa {
            font-family: 'DejaVu Sans', monospace;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="reporte-container">
        @foreach($empleados as $empleado)
            @php
                $totalSegundosRetraso = 0;
                $totalRetrasos = 0;
                $totalOmisiones = 0;
                $totalFaltas = 0;
                
                // Agrupar asistencias por fecha
                $asistenciasPorDia = $empleado->asistencias->groupBy(function($item) {
                    return Carbon\Carbon::parse($item->fecha)->format('Y-m-d');
                });
                
                // Obtener datos del historial activo
                $empresa = $empleado->historialActivo->empresa->razon_social ?? 'N/A';
                $sucursal = $empleado->historialActivo->sucursal->nombre ?? 'N/A';
                $cargo = $empleado->historialActivo->cargo->nombre ?? 'N/A';
            @endphp

            <div class="empleado-container no-break">
                <!-- HEADER GLOBAL DEL REPORTE -->
                <div class="empleado-header">
                    <div>
                        <h3 style="text-align: center; margin: 0 0 5px 0;">REPORTE DE ASISTENCIAS</h3>
                        <!-- BADGE CENTRADO -->
                        <div style="text-align: center; margin: 10px 0;">
                            <span class="badge-info">{{ $empresa }} &#45; {{ $sucursal }}</span>
                        </div>
                        <div style="text-align: center;">
                            <strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i:s') }} 
                            <span class="separator">&#124;</span> 
                            <strong>Período:</strong> {{ $fechaInicio->format('d/m/Y') }} al {{ $fechaFin->format('d/m/Y') }}
                        </div>
                        @if(!empty($filtroSucursal))
                            <div style="text-align: center; margin-top: 5px;">
                                <strong>Sucursal filtrada:</strong> {{ $filtroSucursal }}
                            </div>
                        @endif
                        @if(!empty($filtroBusqueda))
                            <div style="text-align: center;">
                                <strong>Búsqueda:</strong> {{ $filtroBusqueda }}
                            </div>
                        @endif
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #ccc;">
                        <div>
                            <strong>{{ $empleado->nombres }} {{ $empleado->apellidos }}</strong> 
                            <span class="separator">&#124;</span> 
                            <strong>CI:</strong> {{ $empleado->ci }} 
                            <span class="separator">&#124;</span> 
                            <strong>Cargo:</strong> {{ $cargo }}
                        </div>                        
                    </div>
                </div>

                <table class="empleado-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Fecha</th>
                            <th style="width: 60%;">Marcaciones del día</th>
                            <th style="width: 25%;">Tiempo de Retraso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fechas as $date)
                            @php
                                $carbonDate = Carbon\Carbon::parse($date);
                                $dateKey = $carbonDate->format('Y-m-d');
                                $asistenciasDia = $asistenciasPorDia[$dateKey] ?? collect();
                                
                                // Ordenar marcaciones por hora
                                $asistenciasOrdenadas = $asistenciasDia->sortBy('hora');
                                $primeraMarcacion = $asistenciasOrdenadas->first();
                                
                                // Definir horas de referencia (TODAS en formato H:i:s)
                                $horaReferencia = Carbon\Carbon::parse($date . ' 08:30:00');
                                $horaLimite = Carbon\Carbon::parse($date . ' 08:35:59');
                                $horaOmision = Carbon\Carbon::parse($date . ' 10:00:00');
                                
                                $tiempoRetraso = '00:00:00';
                                $mostrarOmision = false;
                                $textoEstado = '';
                            @endphp

                            <tr class="{{ $carbonDate->isWeekend() ? 'weekend' : '' }} no-break">
                                <td>
                                    {{ $carbonDate->format('d/m/Y') }}<br>
                                    <small>{{ $carbonDate->translatedFormat('l') }}</small>
                                </td>
                                
                                <td>
                                    @if($carbonDate->isWeekend())
                                        <span style="color: #6c757d;">Fin de Semana</span>
                                        @php $textoEstado = 'Fin de Semana'; @endphp
                                    @elseif($asistenciasDia->isEmpty())
                                        <span style="color: #6c757d; font-weight: bold;">FALTA</span>
                                        @php 
                                            $totalFaltas++; 
                                            $textoEstado = 'Falta';
                                        @endphp
                                    @else
                                        @php
                                            $horaPrimeraMarcacion = Carbon\Carbon::parse($date . ' ' . $primeraMarcacion->hora);
                                            
                                            // Determinar si es omisión (después de las 10:00)
                                            if ($horaPrimeraMarcacion->greaterThan($horaOmision)) {
                                                $mostrarOmision = true;
                                                $totalOmisiones++;
                                                $textoEstado = 'Omisión';
                                            } 
                                            // Determinar si es retraso (después de las 08:35:59)
                                            elseif ($horaPrimeraMarcacion->greaterThan($horaLimite)) {
                                                $totalRetrasos++;
                                                $textoEstado = 'Retraso';
                                                
                                                // Calcular tiempo de retraso desde las 08:30:00
                                                $diferencia = $horaReferencia->diff($horaPrimeraMarcacion);
                                                $segundosRetraso = $diferencia->h * 3600 + $diferencia->i * 60 + $diferencia->s;
                                                $totalSegundosRetraso += $segundosRetraso;
                                                
                                                // Formatear tiempo de retraso SIEMPRE en H:i:s
                                                $tiempoRetraso = sprintf("%02d:%02d:%02d", $diferencia->h, $diferencia->i, $diferencia->s);
                                            } else {
                                                $textoEstado = 'A tiempo';
                                            }
                                        @endphp                                        
                                        
                                        <!-- Mostrar todas las marcaciones del día con hora completa (HH:MM:SS) -->
                                        <div style="display: flex; flex-wrap: wrap; gap: 5px; align-items: center;">
                                            @foreach($asistenciasOrdenadas as $asistencia)
                                                @php
                                                    $horaMarcacion = Carbon\Carbon::parse($date . ' ' . $asistencia->hora);
                                                    $claseHora = '';
                                                    
                                                    // Resaltar la primera marcación si es retraso
                                                    if ($asistencia->id == $primeraMarcacion->id && isset($horaPrimeraMarcacion) && $horaPrimeraMarcacion->greaterThan($horaLimite) && !$mostrarOmision) {
                                                        $claseHora = 'retraso';
                                                    }
                                                    
                                                    // Verificar si es registro remoto
                                                    $esRemoto = !is_null($asistencia->registro_remoto) && $asistencia->registro_remoto == 1;
                                                    $claseRemoto = $esRemoto ? 'registro-remoto' : '';
                                                    
                                                    // Construir URL de Google Maps
                                                    $urlMapa = '#';
                                                    if($esRemoto && !empty($asistencia->localizacion)) {
                                                        $coordenadas = explode(',', $asistencia->localizacion);
                                                        if(count($coordenadas) == 2) {
                                                            $lat = trim($coordenadas[0]);
                                                            $lng = trim($coordenadas[1]);
                                                            if(is_numeric($lat) && is_numeric($lng)) {
                                                                $urlMapa = "https://www.google.com/maps?q={$lat},{$lng}";
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Formatear hora completa SIEMPRE en HH:MM:SS
                                                    $horaCompleta = date('H:i:s', strtotime($asistencia->hora));
                                                @endphp
                                                
                                                <div class="marcacion-container {{ $esRemoto ? 'marcacion-remota' : '' }}">
                                                    @if($esRemoto && $urlMapa != '#')
                                                        <a href="{{ $urlMapa }}" target="_blank" class="enlace-ubicacion {{ $claseHora }} {{ $claseRemoto }} hora-completa" title="Ver ubicación en Google Maps">
                                                            {{ $horaCompleta }} <span style="font-size: 8px;">(Remoto)</span>
                                                        </a>
                                                    @elseif($esRemoto)
                                                        <span class="{{ $claseHora }} {{ $claseRemoto }} hora-completa" title="Registro Remoto">
                                                            {{ $horaCompleta }} <span style="font-size: 8px;">(Remoto)</span>
                                                        </span>
                                                    @else
                                                        <span class="{{ $claseHora }} hora-completa">
                                                            {{ $horaCompleta }}
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                @if(!$loop->last)
                                                    <span class="separator">&#45;</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                
                                <td class="tiempo-retraso">
                                    @if(!$carbonDate->isWeekend() && !empty($primeraMarcacion) && !$mostrarOmision && isset($textoEstado) && $textoEstado == 'Retraso')
                                        <strong class="retraso">{{ $tiempoRetraso }}</strong>
                                    @elseif(isset($textoEstado) && $textoEstado == 'Omisión')
                                        <span class="omision">Omisión</span>
                                    @elseif(isset($textoEstado) && $textoEstado == 'Falta')
                                        <span style="color: #6c757d;">&#45;&#45;&#45;</span>
                                    @elseif($carbonDate->isWeekend())
                                        <span style="color: #6c757d;">&#45;&#45;&#45;</span>
                                    @else
                                        00:00:00
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        
                        <!-- FILA DE TOTALES POR EMPLEADO -->
                        @php
                            $horasTotal = floor($totalSegundosRetraso / 3600);
                            $minutosTotal = floor(($totalSegundosRetraso % 3600) / 60);
                            $segundosTotal = $totalSegundosRetraso % 60;
                            
                            // SIEMPRE formatear como H:i:s
                            $tiempoTotalRetraso = sprintf("%02d:%02d:%02d", $horasTotal, $minutosTotal, $segundosTotal);
                        @endphp
                        
                        <tr class="total-row no-break">
                            <td colspan="2" style="text-align: right;">
                                <strong>RESUMEN:</strong> 
                                Retrasos: {{ $totalRetrasos }} 
                                <span class="separator">&#124;</span> 
                                Omisiones: {{ $totalOmisiones }} 
                                <span class="separator">&#124;</span> 
                                Faltas: {{ $totalFaltas }} 
                                <span class="separator">&#124;</span> 
                                <strong>Total tiempo retraso:</strong>
                            </td>
                            <td class="tiempo-retraso">
                                <strong>{{ $tiempoTotalRetraso }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Pie de página -->
                <div style="text-align: center; margin-top: 10px; font-size: 8px; color: #6c757d;">
                    Página {{ $loop->iteration }} de {{ $empleados->count() }} 
                    <span class="separator">&#124;</span> 
                    Documento generado el {{ now()->format('d/m/Y H:i:s') }}
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>