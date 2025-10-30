<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo Compartido</title>
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
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                                📤 Grupo Compartido
                            </h1>
                            <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">
                                Confirmación de compartir grupo
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
                                            ¡Hola {{ $nombrePropietario }}! 👋
                                        </p>
                                        <p style="font-size: 16px; color: #555555; margin: 0 0 30px 0; line-height: 1.6;">
                                            Vemos que acabas de compartir el grupo <strong style="color: #667eea;">{{ $nombreGrupo }}</strong> con 
                                            @if($cantidadUsuarios === 1)
                                                la siguiente persona:
                                            @else
                                                las siguientes {{ $cantidadUsuarios }} personas:
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Lista de usuarios compartidos -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #667eea;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 15px 0; color: #888888; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">
                                            👥 Usuarios agregados:
                                        </p>
                                        
                                        @foreach($usuariosCompartidos as $usuario)
                                        <div style="background-color: #ffffff; border-radius: 6px; padding: 12px 15px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                            <p style="margin: 0; color: #333333; font-size: 15px; font-weight: 600;">
                                                 {{ $usuario['name'] }}
                                            </p>
                                            <p style="margin: 5px 0 0 0; color: #666666; font-size: 13px;">
                                                 {{ $usuario['email'] }}
                                            </p>
                                        </div>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Mensaje informativo -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #e7f3ff; border-radius: 8px; border-left: 4px solid #0066cc; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #004085; font-size: 14px; line-height: 1.6;">
                                            <strong>📌 Importante:</strong> 
                                            @if($cantidadUsuarios === 1)
                                                Esta persona ahora puede ver y trabajar en las listas y tareas de este grupo.
                                            @else
                                                Estas {{ $cantidadUsuarios }} personas ahora pueden ver y trabajar en las listas y tareas de este grupo.
                                            @endif
                                            Podrán colaborar contigo en tiempo real.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Botón de acción -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{{ env('APP_URL') }}/tasks/grupos/list" 
                                           style="display: inline-block; background: linear-gradient(45deg, rgb(44, 154, 233), rgb(207, 56, 227)); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s ease;">
                                            📋 Ver Mi Grupo
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Alerta de seguridad -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.6;">
                                            <strong>🔒 Seguridad:</strong> Si usted no realizó esta acción, le solicitamos comunicarse con el administrador del sistema a la brevedad.
                                        </p>
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
                                <strong>Equipo de Baby Ballet Market®</strong>
                            </p>
                            
                            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
                            
                            <p style="margin: 0; color: #999999; font-size: 12px; text-align: center; line-height: 1.8;">
                                Este es un correo automático generado por el sistema.<br>
                                Por favor, no respondas a este mensaje.<br>
                                <br>
                                © {{ date('Y') }} International Dancing Corporation SA de SV. Todos los derechos reservados.
                            </p>
                            
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>