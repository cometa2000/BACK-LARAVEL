<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Has sido agregado a un grupo</title>
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
                        <td style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                                 ¡Bienvenido al Equipo!
                            </h1>
                            <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">
                                Has sido agregado a un nuevo grupo
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
                                            ¡Hola {{ $nombreInvitado }}! 
                                        </p>
                                        <p style="font-size: 16px; color: #555555; margin: 0 0 30px 0; line-height: 1.6;">
                                            Te informamos que <strong style="color: #11998e;">{{ $nombrePropietario }}</strong> te ha agregado a un grupo. ¡Ahora puedes trabajar en equipo y colaborar en las tareas del proyecto!
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Card del grupo -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%); border-radius: 10px; border-left: 5px solid #11998e; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        
                                        <p style="margin: 0 0 10px 0; color: #888888; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">
                                            📁 Nombre del grupo:
                                        </p>
                                        
                                        <h2 style="margin: 0 0 15px 0; color: #11998e; font-size: 28px; font-weight: 700;">
                                            {{ $nombreGrupo }}
                                        </h2>
                                        
                                        <p style="margin: 0; color: #666666; font-size: 14px;">
                                            <strong>👤 Propietario:</strong> {{ $nombrePropietario }}
                                        </p>
                                        
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Beneficios -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 15px 0; color: #333333; font-size: 16px; font-weight: 600;">
                                             Ahora puedes:
                                        </p>
                                        
                                        <div style="background-color: #f8f9fa; border-radius: 6px; padding: 15px; margin-bottom: 10px;">
                                            <p style="margin: 0; color: #555555; font-size: 14px; line-height: 1.6;">
                                                 Ver y editar las tareas del grupo
                                            </p>
                                        </div>
                                        
                                        <div style="background-color: #f8f9fa; border-radius: 6px; padding: 15px; margin-bottom: 10px;">
                                            <p style="margin: 0; color: #555555; font-size: 14px; line-height: 1.6;">
                                                 Crear y organizar listas de tareas
                                            </p>
                                        </div>
                                        
                                        <div style="background-color: #f8f9fa; border-radius: 6px; padding: 15px; margin-bottom: 10px;">
                                            <p style="margin: 0; color: #555555; font-size: 14px; line-height: 1.6;">
                                                 Colaborar con otros miembros del equipo
                                            </p>
                                        </div>
                                        
                                        <div style="background-color: #f8f9fa; border-radius: 6px; padding: 15px;">
                                            <p style="margin: 0; color: #555555; font-size: 14px; line-height: 1.6;">
                                                 Seguir el progreso de los proyectos
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Consejo útil -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #e7f3ff; border-radius: 8px; border-left: 4px solid #0066cc; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #004085; font-size: 14px; line-height: 1.6;">
                                            <strong> Consejo:</strong> Ingresa al grupo para ver las tareas actuales y comenzar a colaborar con tu equipo. ¡La comunicación es clave para el éxito!
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Botón de acción -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{{ env('APP_URL') }}/tasks/grupos/list" 
                                           style="display: inline-block; background: linear-gradient(45deg, #11998e, #38ef7d); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(17, 153, 142, 0.4); transition: all 0.3s ease;">
                                             Acceder al Grupo
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Alerta de seguridad -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.6;">
                                            <strong>🔒 Seguridad:</strong> Si crees que has recibido este correo por error, por favor contacta al administrador del sistema.
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