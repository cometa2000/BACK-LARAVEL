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
            <img src="https://img.icons8.com/ios/80/ffffff/add-user-group-man-man.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                ¡Te han agregado a un grupo!
            </div>
            <div style="color:#e8e7ff; font-size:15px; margin-top:6px;">
                Ahora formas parte de un nuevo equipo
            </div>
        </div>

        <div style="padding:35px 35px 10px 35px;">

            <!-- GREETING -->
            <div style="font-size:16px; color:#1e293b; line-height:1.6; margin-bottom:25px;">
                Hola <strong style="color:#667eea;">{{ $nombreInvitado }}</strong>,<br><br>
                <strong style="color:#667eea;">{{ $nombrePropietario }}</strong> te ha agregado al grupo de trabajo. Ahora puedes colaborar con el equipo en sus proyectos.
            </div>

            <!-- GROUP CARD -->
            <div style="background:linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border-radius:14px; padding:25px; margin-bottom:25px; border:2px solid #e2e8f0; text-align:center;">
                <img src="https://img.icons8.com/ios/80/667eea/folder-invoices.png" style="width:64px; margin-bottom:12px;">
                <div style="color:#718096; font-size:11px; text-transform:uppercase; letter-spacing:1.2px; font-weight:600; margin-bottom:8px;">
                    Has sido agregado al grupo
                </div>
                <div style="color:#1a202c; font-size:26px; font-weight:700; margin-bottom:15px;">
                    {{ $nombreGrupo }}
                </div>
                <div style="background:#ffffff; border-radius:10px; padding:12px; border:1px solid #e2e8f0; display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/667eea/user.png" style="width:18px; vertical-align:middle; margin-right:6px;">
                    <span style="color:#667eea; font-size:14px; font-weight:600; vertical-align:middle;">Compartido por: {{ $nombrePropietario }}</span>
                </div>
            </div>

            <!-- PERMISSIONS -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:15px;">
                <img src="https://img.icons8.com/ios/48/667eea/key.png" style="width:26px; margin-right:10px;">
                ¿Qué puedes hacer?
            </div>

            <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:12px; border:1px solid #e2e8f0;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:40px; vertical-align:top;">
                            <img src="https://img.icons8.com/ios/48/48bb78/checkmark.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e293b; font-size:15px; font-weight:600; margin-bottom:4px;">Ver y crear listas</div>
                            <div style="color:#718096; font-size:13px; line-height:1.5;">Organiza el trabajo del equipo en diferentes listas</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:12px; border:1px solid #e2e8f0;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:40px; vertical-align:top;">
                            <img src="https://img.icons8.com/ios/48/48bb78/checkmark.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e293b; font-size:15px; font-weight:600; margin-bottom:4px;">Gestionar tareas</div>
                            <div style="color:#718096; font-size:13px; line-height:1.5;">Crea, edita y completa tareas del grupo</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:25px; border:1px solid #e2e8f0;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:40px; vertical-align:top;">
                            <img src="https://img.icons8.com/ios/48/48bb78/checkmark.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e293b; font-size:15px; font-weight:600; margin-bottom:4px;">Colaborar en tiempo real</div>
                            <div style="color:#718096; font-size:13px; line-height:1.5;">Trabaja junto con tu equipo y mantente sincronizado</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- INFO BOX -->
            <div style="background:#dbeafe; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #3b82f6;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/1e40af/rocket.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e3a8a; font-size:14px; line-height:1.6;">
                                <strong>¡Comienza ahora!</strong> Accede al grupo y empieza a colaborar con tu equipo. Todas tus acciones estarán sincronizadas en tiempo real.
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
                    Acceder al Grupo
                </a>
            </div>

            <!-- SECURITY WARNING -->
            <div style="background:#fffbeb; border-radius:12px; padding:20px; border-left:4px solid #f59e0b;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/d97706/lock.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#78350f; font-size:13px; line-height:1.6;">
                                <strong>Seguridad:</strong> Si no esperabas este correo, comunícate con {{ $nombrePropietario }} o el administrador del sistema.
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