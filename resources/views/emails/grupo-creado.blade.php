<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo Creado</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7fa;">
    
    <!-- Contenedor principal -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f7fa; padding: 40px 20px;">
        <tr>
            <td align="center">
                
                <!-- Contenedor del email (max 600px) -->
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    
                    <!-- Header con gradiente -->
                    <tr>
                        <td style="background: linear-gradient(45deg, rgb(44, 154, 233), rgb(207, 56, 227)); padding: 40px 30px; text-align: center;">
                            
                            <!-- Logo (Reemplaza con tu logo real) -->
                            <img src="https://babyballet.mx/wp-content/uploads/2023/01/babyballet-academia-de-danza-150x107-1.png" alt="Logo" style="max-width: 100px; margin-bottom: 20px;">
                            
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                Confirmación de creación de grupo
                            </h1>
                            
                        </td>
                    </tr>
                    
                    <!-- Contenido principal -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            
                            <!-- Saludo -->
                            <p style="font-size: 18px; color: #333333; margin: 0 0 20px 0; line-height: 1.6;">
                                Hola <strong style="color: #667eea;">{{ $nombreUsuario }}</strong>.
                            </p>
                            
                            <!-- Mensaje principal -->
                            <p style="font-size: 16px; color: #555555; margin: 0 0 30px 0; line-height: 1.6;">
                                Le informamos que se ha creado exitosamente un nuevo grupo en el sistema de gestión de tareas.
                            </p>
                            
                            <!-- Card del grupo -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%); border-radius: 10px; border-left: 5px solid #667eea; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        
                                        <p style="margin: 0 0 10px 0; color: #888888; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">
                                            📁 Nombre del grupo:
                                        </p>
                                        
                                        <h2 style="margin: 0; color: #667eea; font-size: 28px; font-weight: 700;">
                                            {{ $nombreGrupo }}
                                        </h2>
                                        
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Consejo útil -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff9e6; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #856404; font-size: 14px; line-height: 1.6;">
                                            <strong>💡 Consejo:</strong> Ahora puedes comenzar a organizar tus tareas, crea listas y asigna responsables para mantener todo en orden.
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
                                            📋 Ver Mis Grupos
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Alerta de seguridad -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #e7f3ff; border-radius: 8px; border-left: 4px solid #0066cc; margin-top: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #004085; font-size: 13px; line-height: 1.6;">
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
                                <strong>Equipo de Baby Ballet Market®{{ config('app.name') }}</strong>
                            </p>
                            
                            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
                            
                            <p style="margin: 0; color: #999999; font-size: 12px; text-align: center; line-height: 1.8;">
                                Este es un correo automático generado por el sistema.<br>
                                Por favor, no respondas a este mensaje.<br>
                                <br>
                                © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                            </p>
                            
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>