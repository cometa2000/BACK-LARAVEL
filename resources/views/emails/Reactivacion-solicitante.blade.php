<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
<div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

    <!-- HEADER -->
    <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg,#f59e0b,#d97706);">
        <img src="https://img.icons8.com/ios/80/ffffff/paper-plane.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
        <div style="font-size:28px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
            Solicitud enviada
        </div>
        <div style="color:#fef3c7; font-size:15px; margin-top:6px;">
            Tu solicitud de reactivación fue enviada al propietario
        </div>
    </div>

    <div style="padding:35px 35px 10px 35px;">

        <!-- SALUDO -->
        <div style="font-size:16px; color:#1e293b; line-height:1.7; margin-bottom:25px;">
            Hola <strong style="color:#d97706;">{{ $nombreSolicitante }}</strong>,<br><br>
            Hemos enviado tu solicitud de reactivación al propietario del grupo
            <strong>{{ $nombreGrupo }}</strong>. En cuanto haya una respuesta o cambio en la tarea, te lo informaremos de inmediato.
        </div>

        <!-- TARJETA DE TAREA -->
        <div style="font-size:16px; font-weight:bold; color:#1e293b; margin-bottom:10px;">
            <img src="https://img.icons8.com/ios/48/d97706/note.png" style="width:22px; vertical-align:middle; margin-right:8px;">
            Detalles de la solicitud
        </div>
        <div style="background:#fffbeb; border-radius:12px; padding:22px; border:1px solid #fde68a; margin-bottom:25px; line-height:1.8;">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="color:#78350f; font-size:13px; font-weight:600; width:45%; padding-bottom:8px;">Tarea:</td>
                    <td style="color:#1e293b; font-size:14px; padding-bottom:8px;">{{ $nombreTarea }}</td>
                </tr>
                <tr>
                    <td style="color:#78350f; font-size:13px; font-weight:600; padding-bottom:8px;">Grupo:</td>
                    <td style="color:#1e293b; font-size:14px; padding-bottom:8px;">{{ $nombreGrupo }}</td>
                </tr>
                <tr>
                    <td style="color:#78350f; font-size:13px; font-weight:600; padding-bottom:8px;">Fecha de vencimiento original:</td>
                    <td style="color:#ef4444; font-size:14px; font-weight:600; padding-bottom:8px;">{{ $fechaVencimiento }}</td>
                </tr>
                <tr>
                    <td style="color:#78350f; font-size:13px; font-weight:600;">Estado actual:</td>
                    <td>
                        <span style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:700;">
                            VENCIDA
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- INFO BOX -->
        <div style="background:#fef3c7; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #f59e0b;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top; width:32px;">
                        <img src="https://img.icons8.com/ios/48/d97706/clock.png" style="width:24px;">
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#78350f; font-size:14px; line-height:1.6;">
                            <strong>¿Qué sigue?</strong> El propietario del grupo revisará tu solicitud.
                            Cuando modifique la fecha de vencimiento de la tarea, recibirás otro correo
                            confirmando que ya puedes continuar trabajando en ella.
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- BOTÓN -->
        <div style="text-align:center; margin-bottom:40px;">
            <a href="{{ $urlTarea }}"
               style="background:linear-gradient(135deg,#f59e0b,#d97706); padding:14px 32px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(245,158,11,0.5); display:inline-block;">
                <img src="https://img.icons8.com/ios/48/ffffff/external-link.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                Ver tablero del grupo
            </a>
        </div>

    </div>

    <!-- FOOTER -->
    <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
        Saludos cordiales,<br>
        <strong style="color:#d97706;">Equipo de Baby Ballet Marbet®</strong><br><br>
        © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
    </div>

</div>
</div>

</body>
</html>