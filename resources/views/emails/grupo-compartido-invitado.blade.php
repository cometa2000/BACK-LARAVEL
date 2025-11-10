<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación a Grupo</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: rgb(228, 228, 228);">
    
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 60px 20px;">
        <tr>
            <td align="center">
                
                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
                    
                    <!-- Header Section -->
                    <tr>
                        <td style="padding: 50px 40px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            
                            <!-- Icon Circle -->
                            <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 100px; height: 100px; margin: 0 auto 24px; display: inline-block; line-height: 100px;">
                                <img src="https://img.icons8.com/material-rounded/96/ffffff/add-user-group-man-man.png" style="width: 56px; vertical-align: middle;">
                            </div>
                            
                            <h1 style="margin: 0 0 12px 0; color: #ffffff; font-size: 32px; font-weight: 700; letter-spacing: -0.5px;">
                                ¡Bienvenido al Equipo!
                            </h1>
                            <p style="margin: 0; color: rgba(255,255,255,0.95); font-size: 16px; font-weight: 500;">
                                Has sido invitado a colaborar en un grupo
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content Section -->
                    <tr>
                        <td style="padding: 50px 40px;">
                            
                            <!-- Greeting -->
                            <p style="font-size: 18px; color: #1a202c; margin: 0 0 12px 0; font-weight: 600;">
                                Hola {{ $nombreInvitado }},
                            </p>
                            <p style="font-size: 15px; color: #4a5568; margin: 0 0 32px 0; line-height: 1.6;">
                                <strong style="color: #667eea;">{{ $nombrePropietario }}</strong> te ha agregado como miembro de su grupo. Ahora puedes colaborar, gestionar tareas y trabajar en equipo de manera eficiente.
                            </p>
                            
                            <!-- Group Info Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border-radius: 16px; margin-bottom: 32px; border: 2px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 28px;">
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="vertical-align: top; width: 48px;">
                                                    <img src="https://img.icons8.com/material-rounded/96/667eea/folder-invoices.png" style="width: 40px;">
                                                </td>
                                                <td style="padding-left: 16px;">
                                                    <p style="margin: 0 0 4px 0; color: #718096; font-size: 12px; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 600;">
                                                        Nombre del Grupo
                                                    </p>
                                                    <h2 style="margin: 0 0 16px 0; color: #1a202c; font-size: 24px; font-weight: 700;">
                                                        {{ $nombreGrupo }}
                                                    </h2>
                                                    <table cellpadding="0" cellspacing="0" style="margin: 0;">
                                                        <tr>
                                                            <td style="vertical-align: middle; padding-right: 8px;">
                                                                <img src="https://img.icons8.com/material-rounded/96/667eea/user.png" style="width: 18px; vertical-align: middle;">
                                                            </td>
                                                            <td style="vertical-align: middle;">
                                                                <span style="color: #4a5568; font-size: 14px; font-weight: 500;">{{ $nombrePropietario }}</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Benefits Section -->
                            <div style="margin-bottom: 32px;">
                                <h3 style="margin: 0 0 20px 0; color: #1a202c; font-size: 18px; font-weight: 700;">
                                    <img src="https://img.icons8.com/material-rounded/96/667eea/star--v1.png" style="width: 24px; vertical-align: middle; margin-right: 8px;">
                                    Ahora puedes
                                </h3>
                                
                                <!-- Benefit Item -->
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                    <tr>
                                        <td style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 16px;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="vertical-align: middle; padding-right: 12px;">
                                                        <img src="https://img.icons8.com/material-rounded/96/48bb78/checked.png" style="width: 24px;">
                                                    </td>
                                                    <td style="vertical-align: middle;">
                                                        <span style="color: #4a5568; font-size: 14px; line-height: 1.5;">Ver y editar tareas colaborativamente</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                    <tr>
                                        <td style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 16px;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="vertical-align: middle; padding-right: 12px;">
                                                        <img src="https://img.icons8.com/material-rounded/96/48bb78/checked.png" style="width: 24px;">
                                                    </td>
                                                    <td style="vertical-align: middle;">
                                                        <span style="color: #4a5568; font-size: 14px; line-height: 1.5;">Crear y organizar listas de tareas</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                    <tr>
                                        <td style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 16px;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="vertical-align: middle; padding-right: 12px;">
                                                        <img src="https://img.icons8.com/material-rounded/96/48bb78/checked.png" style="width: 24px;">
                                                    </td>
                                                    <td style="vertical-align: middle;">
                                                        <span style="color: #4a5568; font-size: 14px; line-height: 1.5;">Comunicarte con tu equipo en tiempo real</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 16px;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="vertical-align: middle; padding-right: 12px;">
                                                        <img src="https://img.icons8.com/material-rounded/96/48bb78/checked.png" style="width: 24px;">
                                                    </td>
                                                    <td style="vertical-align: middle;">
                                                        <span style="color: #4a5568; font-size: 14px; line-height: 1.5;">Seguir el progreso de proyectos</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Info Box -->
                            <div style="background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%); border-radius: 12px; padding: 20px; margin-bottom: 32px; border-left: 4px solid #38b2ac;">
                                <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="vertical-align: top; padding-right: 12px;">
                                            <img src="https://img.icons8.com/material-rounded/96/319795/info.png" style="width: 24px;">
                                        </td>
                                        <td style="vertical-align: top;">
                                            <p style="margin: 0; color: #234e52; font-size: 14px; line-height: 1.6;">
                                                <strong>Consejo:</strong> Ingresa al grupo para explorar las tareas actuales y comenzar a colaborar. La comunicación efectiva es clave para el éxito del equipo.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0 32px 0;">
                                        <a href="{{ env('APP_URL') }}/tasks/grupos/list" 
                                           style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 18px 48px; border-radius: 12px; font-size: 16px; font-weight: 700; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="vertical-align: middle; padding-right: 8px;">
                                                        <img src="https://img.icons8.com/material-rounded/96/ffffff/login-rounded-right.png" style="width: 20px; vertical-align: middle;">
                                                    </td>
                                                    <td style="vertical-align: middle;">
                                                        <span style="color: #ffffff; font-size: 16px; font-weight: 700;">Acceder al Grupo</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Security Notice -->
                            <div style="background: #fffbeb; border-radius: 12px; padding: 20px; border-left: 4px solid #f59e0b;">
                                <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="vertical-align: top; padding-right: 12px;">
                                            <img src="https://img.icons8.com/material-rounded/96/d97706/lock.png" style="width: 24px;">
                                        </td>
                                        <td style="vertical-align: top;">
                                            <p style="margin: 0; color: #78350f; font-size: 13px; line-height: 1.6;">
                                                <strong>Seguridad:</strong> Si crees que has recibido este correo por error, contacta al administrador del sistema.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background: #f7fafc; padding: 40px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 16px 0; color: #4a5568; font-size: 14px; text-align: center; line-height: 1.6;">
                                Saludos cordiales,<br>
                                <strong style="color: #667eea;">Equipo de Baby Ballet Marbet®</strong>
                            </p>
                            
                            <div style="height: 1px; background: #e2e8f0; margin: 24px 0;"></div>
                            
                            <p style="margin: 0; color: #a0aec0; font-size: 12px; text-align: center; line-height: 1.8;">
                                Este es un correo automático. Por favor, no respondas a este mensaje.<br>
                                © {{ date('Y') }} International Dancing Corporation S.A. de S.V.<br>
                                Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>