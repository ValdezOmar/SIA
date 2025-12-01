<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Códigos QR de Equipos</title>
    <style>
        @page {
            margin: 5mm;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #2d3748;            
            margin: 0;
            padding: 2;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding: 25px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }
        
        .header .subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin: 0 0 10px 0;
            font-weight: 300;
        }
        
        .header .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 0px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            page-break-inside: avoid;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-counter {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }
        
        .card-title {
            text-align: center;
            margin-bottom: 5px;
        }
        
        .card-title .code {
            font-size: 15px;
            font-weight: 800;
            color: #2d3748;
            background: #f8fafc;
            padding: 6px 15px;
            border-radius: 2px;
            display: inline-block;
            border: 0.8px solid #e2e8f0;
        }
        
        .qr-container {
            text-align: center;
            margin-bottom: 10px;
            padding: 5px;           
            
        }
        
        .qr-wrapper {
            position: relative;            
            display: inline-block;
            padding: 5px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .qr-image {
            width: 200px;
            height: 200px;
            display: block;
            margin: 0 auto;
            border-radius: 6px;
        }    

        .info-item {
            display: flex;
            align-items: center;
            padding: 5px 180px;            
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .label {
            flex: 0 0 80px;
            font-weight: 600;
            color: #4a5568;
            font-size: 10px;
        }
        
        .value {
            flex: 1;
            color: #2d3748;
            font-size: 11px;
        }
        
        .url-container {
            background: #f8fafc;
            padding: 10px;
            border-radius: 6px;
            margin-top: 15px;
            border-left: 3px solid #667eea;
        }
        
        .info-grid {
            font-size: 14px;
            color: #718096;            
            font-weight: 600;
        }
        
        .url-value {
            font-size: 9px;
            color: #4a5568;
            word-break: break-all;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            color: #718096;
            font-size: 10px;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        /* Versión compacta para muchos equipos */
        .compact-card {
            display: flex;
            gap: 15px;
            align-items: center;
            padding: 15px;
        }
        
        .compact-qr {
            flex: 0 0 100px;
            text-align: center;
        }
        
        .compact-info {
            flex: 1;
        }
        
        /* Grid responsivo */
        @media (max-width: 768px) {
            .card-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media print {
            body {
                background: white;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
            
            .header {
                box-shadow: none;
                background: #667eea;
            }
        }
    </style>
</head>
<body>    
    
    
        <!-- Diseño elegante en grid para pocos equipos -->
        <div class="card-container">
            @foreach($equipos as $index => $equipo)
                <div class="card">
                    <div class="card-counter">{{ $index + 1 }}</div>                    

                    <div class="card-title">                        
                        <span class="code">SOLICITUD DE SOPORTE TECNICO <br> {{ $equipo['codigo'] }}</span>
                    </div>                    
                    
                    <div class="qr-container">
                        <div class="qr-wrapper">
                            <img src="{{ $equipo['qr_code'] }}" alt="QR Code" class="qr-image">
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Marca:</span>
                            <span class="value">{{ $equipo['marca'] }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Modelo:</span>
                            <span class="value">{{ $equipo['modelo'] }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">N° Serie:</span>
                            <span class="value">{{ $equipo['num_serie'] }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Empresa:</span>
                            <span class="value">{{ $equipo['empresa'] }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Cliente:</span>
                            <span class="value">{{ $equipo['cliente'] }}</span>
                        </div>
                    </div>                  
                    
                    
                    <div style="text-align: center; margin-top: 12px;">
                        <span style="font-size: 9px; color: #718096; font-style: italic;">
                            Escanear código para ver detalles completos
                        </span>
                    </div>
                </div>
            @endforeach
        </div>   
   
    
    
</body>
</html>