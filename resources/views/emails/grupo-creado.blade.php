<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background: rgb(228, 228, 228); font-family: Arial, Helvetica, sans-serif;">

<div style="width:100%; padding:40px 0;">

    <div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; padding:0; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

        <!-- HEADER GRADIENT -->
        <div style="text-align:center; padding:50px 20px; background: linear-gradient(135deg, #667eea, #764ba2);">
            <img src="https://img.icons8.com/ios/80/ffffff/add-folder.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                ¡Grupo Creado!
            </div>
            <div style="color:#e8e7ff; font-size:15px; margin-top:6px;">
                Tu nuevo espacio de trabajo está listo
            </div>
        </div>

        <div style="padding:35px 35px 10px 35px;">

            <!-- GREETING -->
            <div style="font-size:16px; color:#1e293b; line-height:1.6; margin-bottom:25px;">
                Hola <strong style="color:#667eea;">{{ $nombreUsuario }}</strong>,<br><br>
                Confirmamos que tu grupo ha sido creado exitosamente. Ya puedes comenzar a organizar tus proyectos y colaborar con tu equipo.
            </div>

            <!-- GROUP CARD -->
            <div style="background:linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border-radius:14px; padding:25px; margin-bottom:25px; border:2px solid #e2e8f0; text-align:center;">
                <img src="https://img.icons8.com/ios/80/667eea/folder-invoices.png" style="width:64px; margin-bottom:12px;">
                <div style="color:#718096; font-size:11px; text-transform:uppercase; letter-spacing:1.2px; font-weight:600; margin-bottom:8px;">
                    Nombre del Grupo
                </div>
                <div style="color:#1a202c; font-size:26px; font-weight:700; margin-bottom:15px;">
                    {{ $nombreGrupo }}
                </div>
                <div style="background:#ffffff; border-radius:10px; padding:12px; border:1px solid #e2e8f0; display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/48bb78/checkmark.png" style="width:18px; vertical-align:middle; margin-right:6px;">
                    <span style="color:#2f855a; font-size:14px; font-weight:600; vertical-align:middle;">Estado: Activo</span>
                </div>
            </div>

            <!-- NEXT STEPS -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:15px;">
                <img src="https://img.icons8.com/ios/48/667eea/rocket.png" style="width:26px; margin-right:10px;">
                Próximos Pasos
            </div>

            <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:12px; border:1px solid #e2e8f0;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:40px; vertical-align:top;">
                            <div style="background:linear-gradient(135deg, #667eea, #764ba2); border-radius:50%; width:36px; height:36px; align-items:center; justify-content:center; color:white; font-weight:700; font-size:16px; text-align:center; line-height:36px;">
                                1
                            </div>
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e293b; font-size:15px; font-weight:600; margin-bottom:4px;">Crea tus listas</div>
                            <div style="color:#718096; font-size:13px; line-height:1.5;">Organiza tu trabajo en listas como "Por hacer", "En progreso" y "Completado"</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:12px; border:1px solid #e2e8f0;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:40px; vertical-align:top;">
                            <div style="background:linear-gradient(135deg, #667eea, #764ba2); border-radius:50%; width:36px; height:36px; align-items:center; justify-content:center; color:white; font-weight:700; font-size:16px; text-align:center; line-height:36px;">
                                2
                            </div>
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e293b; font-size:15px; font-weight:600; margin-bottom:4px;">Agrega tareas</div>
                            <div style="color:#718096; font-size:13px; line-height:1.5;">Define las tareas específicas con fechas límite y prioridades</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:25px; border:1px solid #e2e8f0;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:40px; vertical-align:top;">
                            <div style="background:linear-gradient(135deg, #667eea, #764ba2); border-radius:50%; width:36px; height:36px; align-items:center; justify-content:center; color:white; font-weight:700; font-size:16px; text-align:center; line-height:36px;">
                                3
                            </div>
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e293b; font-size:15px; font-weight:600; margin-bottom:4px;">Invita a tu equipo</div>
                            <div style="color:#718096; font-size:13px; line-height:1.5;">Comparte el grupo con tus colaboradores y trabajen juntos</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- TIP BOX -->
            <div style="background:#fef3c7; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #f59e0b;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/d97706/idea.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#78350f; font-size:14px; line-height:1.6;">
                                <strong>Consejo Pro:</strong> Comienza con una estructura simple y ajusta según las necesidades de tu equipo. Recuerda asignar responsables a cada tarea.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- BUTTON CTA -->
            <div style="text-align:center; margin-top:10px; margin-bottom:40px;">
                <a href="https://crmbbm.preubasbbm.com/tasks/grupos/list" 
                   style="background: linear-gradient(135deg, #667eea, #764ba2); padding:14px 32px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(102,126,234,0.6); display:inline-block;">
                    <img src="https://img.icons8.com/material-rounded/96/ffffff/login-rounded-right.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                    Ir a Mis Grupos
                </a>
            </div>

            <!-- SECURITY WARNING -->
            <div style="background:#dbeafe; border-radius:12px; padding:20px; border-left:4px solid #3b82f6;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/1e40af/security-checked.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e3a8a; font-size:13px; line-height:1.6;">
                                <strong>Seguridad:</strong> Si no realizaste esta acción, contacta al administrador inmediatamente.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- FOOTER -->
        <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
            Saludos cordiales,<br>
            <strong style="color:#667eea;">Equipo de Baby Ballet Marbet®</strong><br><br>
            © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
        </div>

    </div>

</div>

</body>
</html>