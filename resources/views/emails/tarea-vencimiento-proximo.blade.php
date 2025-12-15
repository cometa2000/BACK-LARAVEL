<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarea Próxima a Vencer</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5;">
    
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 0;">
        <tr>
            <td align="center">
                
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                                ⏰ Tarea Próxima a Vencer
                            </h1>
                            <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">
                                @if($diasRestantes == 1)
                                    Esta tarea vence mañana
                                @else
                                    Esta tarea vence en {{ $diasRestantes }} días
                                @endif
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            
                            <!-- Saludo personalizado -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="font-size: 18px; color: #333333; margin: 0 0 10px 0; font-weight: 600;">
                                            ¡Hola {{ $usuario->name }}! 👋
                                        </p>
                                        <p style="font-size: 16px; color: #555555; margin: 0 0 30px 0; line-height: 1.6;">
                                            Te recordamos que la siguiente tarea está próxima a su fecha de vencimiento:
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Card de la tarea -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border-radius: 10px; border-left: 5px solid #ff9800; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        
                                        <h2 style="margin: 0 0 15px 0; color: #e65100; font-size: 24px; font-weight: 700;">
                                            📋 {{ $tarea->name }}
                                        </h2>
                                        
                                        @if($tarea->description)
                                        <p style="margin: 0 0 15px 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                            <strong>Descripción:</strong><br>
                                            {{ $tarea->description }}
                                        </p>
                                        @endif
                                        
                                        <!-- Información de vencimiento -->
                                        <div style="background-color: #ffffff; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                                            <p style="margin: 0; color: #333333; font-size: 14px;">
                                                <strong>📅 Fecha de vencimiento:</strong><br>
                                                <span style="font-size: 18px; color: #e65100; font-weight: 700;">
                                                    {{ \Carbon\Carbon::parse($tarea->due_date)->format('d/m/Y H:i') }}
                                                </span>
                                            </p>
                                        </div>
                                        
                                        <!-- Tiempo restante -->
                                        <div style="background-color: rgba(230, 81, 0, 0.1); border-radius: 6px; padding: 15px; text-align: center;">
                                            <p style="margin: 0; color: #e65100; font-size: 16px; font-weight: 700;">
                                                ⏱️ 
                                                @if($diasRestantes == 1)
                                                    Vence mañana
                                                @else
                                                    Vence en {{ $diasRestantes }} días
                                                @endif
                                            </p>
                                        </div>
                                        
                                        <!-- Información adicional -->
                                        @if($tarea->priority)
                                        <p style="margin: 15px 0 0 0; color: #666666; font-size: 13px;">
                                            <strong>Prioridad:</strong> 
                                            <span style="
                                                padding: 4px 8px; 
                                                border-radius: 4px; 
                                                background-color: {{ $tarea->priority == 'high' ? '#f44336' : ($tarea->priority == 'medium' ? '#ff9800' : '#4caf50') }}; 
                                                color: white;
                                                font-weight: 600;
                                            ">
                                                {{ $tarea->priority == 'high' ? 'Alta' : ($tarea->priority == 'medium' ? 'Media' : 'Baja') }}
                                            </span>
                                        </p>
                                        @endif
                                        
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Consejo útil -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #1565c0; font-size: 14px; line-height: 1.6;">
                                            <strong>💡 Consejo:</strong> Revisa el progreso de la tarea y asegúrate de completarla antes de la fecha límite. Si necesitas más tiempo, considera actualizar la fecha de vencimiento.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Botón de acción -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="https://crmbbm.preubasbbm.com/tasks/tareas/tablero/{{ $tarea->grupo_id }}" 
                                           style="display: inline-block; background: linear-gradient(45deg, #ff9800, #f57c00); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.4); transition: all 0.3s ease;">
                                            📋 Ver Tarea Completa
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; border-top: 1px solid #e0e0e0;">
                            
                            <p style="margin: 0 0 15px 0; color: #666666; font-size: 14px; text-align: center;">
                                Saludos cordiales,<br>
                                <strong>Equipo de Baby Ballet Marbet®</strong>
                            </p>
                            
                            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
                            
                            <p style="margin: 0; color: #999999; font-size: 12px; text-align: center; line-height: 1.8;">
                                Este es un correo automático de recordatorio de vencimiento de tarea.<br>
                                Por favor, no respondas a este mensaje.<br>
                                <br>
                                © {{ date('Y') }} International Dancing Corporation S.A. de S.V. Todos los derechos reservados.
                            </p>
                            
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>