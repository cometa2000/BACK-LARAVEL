<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarea Completada</title>
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
                        <td style="background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%); padding: 40px 30px; text-align: center;">
                            <!-- Icono de checkmark -->
                            <div style="background-color: rgba(255,255,255,0.2); border-radius: 50%; width: 80px; height: 80px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                                <div style="font-size: 50px; color: white;">✓</div>
                            </div>
                            
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                                ✅ ¡Tarea Completada!
                            </h1>
                            <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">
                                Una tarea ha sido marcada como completada
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
                                            ¡Hola {{ $nombreUsuario }}! 👋
                                        </p>
                                        <p style="font-size: 16px; color: #555555; margin: 0 0 30px 0; line-height: 1.6;">
                                            @if($esCreador)
                                                Te notificamos que <strong style="color: #2e7d32;">{{ $nombreCompletador }}</strong> ha completado la tarea que creaste.
                                            @else
                                                Te notificamos que <strong style="color: #2e7d32;">{{ $nombreCompletador }}</strong> ha completado una tarea en la que estás asignado.
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Card de la tarea completada -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-radius: 10px; border-left: 5px solid #4caf50; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        
                                        <h2 style="margin: 0 0 15px 0; color: #1b5e20; font-size: 24px; font-weight: 700;">
                                            📋 {{ $tarea->name }}
                                        </h2>
                                        
                                        @if($tarea->description)
                                        <p style="margin: 0 0 15px 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                            <strong>Descripción:</strong><br>
                                            {{ $tarea->description }}
                                        </p>
                                        @endif
                                        
                                        <!-- Información del grupo y lista -->
                                        <div style="background-color: #ffffff; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                                            <p style="margin: 0 0 8px 0; color: #333333; font-size: 14px;">
                                                <strong>📁 Grupo:</strong> {{ $grupo->name }}
                                            </p>
                                            <p style="margin: 0; color: #333333; font-size: 14px;">
                                                <strong>📑 Lista:</strong> {{ $lista->name }}
                                            </p>
                                        </div>
                                        
                                        <!-- Badge de completado -->
                                        <div style="background-color: rgba(76, 175, 80, 0.15); border-radius: 6px; padding: 15px; text-align: center;">
                                            <p style="margin: 0; color: #1b5e20; font-size: 16px; font-weight: 700;">
                                                ✅ Estado: COMPLETADA
                                            </p>
                                        </div>
                                        
                                        <!-- Información de quien completó -->
                                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1);">
                                            <p style="margin: 0; color: #666666; font-size: 13px;">
                                                <strong>👤 Completada por:</strong> {{ $nombreCompletador }}
                                            </p>
                                            <p style="margin: 5px 0 0 0; color: #999999; font-size: 12px;">
                                                {{ now()->format('d/m/Y H:i') }}
                                            </p>
                                        </div>
                                        
                                        @if($tarea->priority)
                                        <p style="margin: 15px 0 0 0; color: #666666; font-size: 13px;">
                                            <strong>Prioridad original:</strong> 
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
                                        
                                        @if($tarea->due_date)
                                        <p style="margin: 10px 0 0 0; color: #666666; font-size: 13px;">
                                            <strong>📅 Fecha límite:</strong> {{ \Carbon\Carbon::parse($tarea->due_date)->format('d/m/Y H:i') }}
                                        </p>
                                        @endif
                                        
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Mensaje de felicitación -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #1565c0; font-size: 14px; line-height: 1.6;">
                                            <strong>🎉 ¡Excelente trabajo!</strong> 
                                            @if($esCreador)
                                                Tu tarea ha sido completada exitosamente. Puedes revisarla haciendo clic en el botón de abajo.
                                            @else
                                                Esta tarea ha sido completada. Puedes revisar los detalles haciendo clic en el botón de abajo.
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Botón de acción -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{{ $urlTarea }}" 
                                           style="display: inline-block; background: linear-gradient(45deg, #4caf50, #2e7d32); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4); transition: all 0.3s ease;">
                                            📋 Ver Detalles de la Tarea
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Información adicional -->
                            @if($esCreador)
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff3e0; border-radius: 8px; border-left: 4px solid #ff9800; margin-top: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #e65100; font-size: 13px; line-height: 1.6;">
                                            <strong>📊 Nota:</strong> Como creador de esta tarea, puedes revisar el trabajo completado y verificar que todo esté en orden.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
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
                                Este es un correo automático de notificación de tarea completada.<br>
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