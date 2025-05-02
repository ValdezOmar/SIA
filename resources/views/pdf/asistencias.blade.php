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
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
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
            padding: 3px;
            text-align: left;
            word-wrap: break-word;
        }
        .empleado-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .empleado-header {
            background-color: #e6e6e6;
            padding: 5px;
            margin-bottom: 5px;
            border-radius: 3px;
            font-weight: bold;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 5px;
            font-size: 9px;
        }
        .title {
            text-align: center;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .weekend {
            background-color: #f9f9f9;
        }
        .omision {
            color: orange;
            font-weight: bold;
        }
        .retraso {
            color: red;
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
            font-family: monospace;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="header-info">
        <div>
            <strong>Fecha de reporte:</strong> {{ now()->format('d/m/Y H:i:s') }}
        </div>
        <div>
            <strong>Período:</strong> {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
        </div>
        @if(!empty($filtroSucursal))
            <div>
                <strong>Sucursal:</strong> {{ $filtroSucursal }}
            </div>
        @endif
        @if(!empty($filtroBusqueda))
            <div>
                <strong>Búsqueda:</strong> {{ $filtroBusqueda }}
            </div>
        @endif
    </div>

    <div class="title">
        <h1>Reporte de Asistencias</h1>
    </div>

    @foreach($empleados as $empleado)
        @php
            $totalSegundosRetraso = 0;
            // Agrupar asistencias por fecha para mejor procesamiento
            $asistenciasPorDia = $empleado->asistencias->groupBy(function($item) {
                return Carbon\Carbon::parse($item->fecha)->format('Y-m-d');
            });
        @endphp

        <div class="empleado-container no-break">
            <div class="empleado-header">
                <strong>Empleado:</strong> {{ $empleado->nombres }} {{ $empleado->apellidos }} | 
                <strong>CI:</strong> {{ $empleado->ci }} | 
                <strong>Sucursal:</strong> {{ $empleado->sucursal }} | 
                <strong>Empresa:</strong> {{ $empleado->empresa }}
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
                            
                            $horaLimite = Carbon\Carbon::today()->setTime(8, 35, 0);
                            $horaEntrada = Carbon\Carbon::today()->setTime(8, 30, 0);
                            $horaOmision = Carbon\Carbon::today()->setTime(10, 0, 0);
                            $tiempoRetraso = '00:00';
                            $mostrarOmision = false;
                        @endphp

                        <tr class="{{ $carbonDate->isWeekend() ? 'weekend' : '' }} no-break">
                            <td>{{ $carbonDate->format('d/m/Y') }} ({{ $carbonDate->translatedFormat('D') }})</td>
                            
                            <td>
                                @if($asistenciasDia->isNotEmpty())
                                    @if($primeraMarcacion)
                                        @php
                                            $horaPrimeraMarcacion = Carbon\Carbon::parse($primeraMarcacion->hora);
                                            $mostrarOmision = $horaPrimeraMarcacion->greaterThan($horaOmision);
                                        @endphp
                                        
                                        @if($mostrarOmision)
                                            <span class="omision">Omisión</span><br>
                                        @endif
                                        
                                        @foreach($asistenciasOrdenadas as $asistencia)
                                            @php
                                                $horaMarcacion = Carbon\Carbon::parse($asistencia->hora);
                                            @endphp
                                            
                                            @if($horaMarcacion->equalTo($horaPrimeraMarcacion))
                                                @if($horaMarcacion->greaterThan($horaLimite) && !$mostrarOmision)
                                                    <span class="retraso">{{ $horaMarcacion->format('H:i:s') }}</span>
                                                @else
                                                    {{ $horaMarcacion->format('H:i:s') }}
                                                @endif
                                            @else
                                                {{ $horaMarcacion->format('H:i:s') }}
                                            @endif
                                            
                                            @if(!$loop->last)
                                                <br>
                                            @endif
                                        @endforeach
                                    @endif
                                @else
                                    {{ $carbonDate->isWeekend() ? 'Fin de Semana' : 'Falta' }}
                                @endif
                            </td>
                            
                            <td class="tiempo-retraso">
                                @if($primeraMarcacion && !$carbonDate->isWeekend() && !$mostrarOmision)
                                    @php
                                        $horaPrimeraMarcacion = Carbon\Carbon::parse($primeraMarcacion->hora);
                                        
                                        if ($horaPrimeraMarcacion->greaterThan($horaLimite)) {
                                            // Calcular diferencia exacta
                                            $diferencia = $horaEntrada->diff($horaPrimeraMarcacion);
                                            
                                            // Formatear según la duración
                                            if ($diferencia->h > 0) {
                                                $tiempoRetraso = sprintf("%02d:%02d:%02d", $diferencia->h, $diferencia->i, $diferencia->s);
                                            } else {
                                                $tiempoRetraso = sprintf("%02d:%02d", $diferencia->i, $diferencia->s);
                                            }
                                            
                                            // Calcular segundos totales para el acumulado
                                            $segundosRetraso = $diferencia->h * 3600 + $diferencia->i * 60 + $diferencia->s;
                                            $totalSegundosRetraso += $segundosRetraso;
                                        }
                                    @endphp
                                    
                                    {{ $tiempoRetraso }}
                                @else
                                    00:00
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @php
                        // Calcular el tiempo total de retraso
                        $horasTotal = floor($totalSegundosRetraso / 3600);
                        $minutosTotal = floor(($totalSegundosRetraso % 3600) / 60);
                        $segundosTotal = $totalSegundosRetraso % 60;
                        
                        if ($horasTotal > 0) {
                            $tiempoTotalRetraso = sprintf("%02d:%02d:%02d", $horasTotal, $minutosTotal, $segundosTotal);
                        } else {
                            $tiempoTotalRetraso = sprintf("%02d:%02d", $minutosTotal, $segundosTotal);
                        }
                    @endphp
                    <tr class="total-row no-break">
                        <td colspan="2" style="text-align: right;"><strong>Total tiempo de retraso:</strong></td>
                        <td class="tiempo-retraso"><strong>{{ $tiempoTotalRetraso }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>