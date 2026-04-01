<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
<div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

    <!-- HEADER -->
    <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg,#ef4444,#dc2626);">
        <img src="https://img.icons8.com/ios/80/ffffff/maintenance.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
        <div style="font-size:28px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
            Solicitud de reactivación
        </div>
        <div style="color:#fee2e2; font-size:15px; margin-top:6px;">
            Un miembro de tu grupo necesita tu ayuda
        </div>
    </div>

    <div style="padding:35px 35px 10px 35px;">

        <!-- SALUDO -->
        <div style="font-size:16px; color:#1e293b; line-height:1.7; margin-bottom:25px;">
            Hola <strong style="color:#dc2626;">{{ $nombrePropietario }}</strong>,<br><br>
            Has recibido una solicitud de reactivación de tarea en el grupo
            <strong>{{ $nombreGrupo }}</strong>. Un miembro de tu grupo no puede continuar trabajando
            porque la tarea venció. Revisa los detalles a continuación y actualiza la fecha si corresponde.
        </div>

        <!-- TARJETA PRINCIPAL -->
        <div style="font-size:16px; font-weight:bold; color:#1e293b; margin-bottom:10px;">
            <img src="https://img.icons8.com/ios/48/dc2626/note.png" style="width:22px; vertical-align:middle; margin-right:8px;">
            Detalles de la solicitud
        </div>
        <div style="background:#fef2f2; border-radius:12px; padding:22px; border:1px solid #fecaca; margin-bottom:25px;">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="color:#991b1b; font-size:13px; font-weight:600; width:45%; padding-bottom:10px; vertical-align:top;">Nombre de la tarea:</td>
                    <td style="color:#1e293b; font-size:15px; font-weight:700; padding-bottom:10px;">{{ $nombreTarea }}</td>
                </tr>
                <tr>
                    <td style="color:#991b1b; font-size:13px; font-weight:600; padding-bottom:10px; vertical-align:top;">Grupo:</td>
                    <td style="color:#1e293b; font-size:14px; padding-bottom:10px;">{{ $nombreGrupo }}</td>
                </tr>
                <tr>
                    <td style="color:#991b1b; font-size:13px; font-weight:600; padding-bottom:10px; vertical-align:top;">Fecha de vencimiento actual:</td>
                    <td style="padding-bottom:10px;">
                        <span style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; border-radius:6px; padding:4px 12px; font-size:14px; font-weight:700;">
                            {{ $fechaVencimiento }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="color:#991b1b; font-size:13px; font-weight:600; padding-bottom:10px; vertical-align:top;">Solicitante:</td>
                    <td style="color:#1e293b; font-size:14px; font-weight:600; padding-bottom:10px;">
                        <img src="https://img.icons8.com/ios/48/1e293b/user.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                        {{ $nombreSolicitante }}
                    </td>
                </tr>
                <tr>
                    <td style="color:#991b1b; font-size:13px; font-weight:600; vertical-align:top;">Fecha de la solicitud:</td>
                    <td style="color:#1e293b; font-size:14px;">{{ $fechaSolicitud }}</td>
                </tr>
            </table>
        </div>

        <!-- INSTRUCCIONES -->
        <div style="font-size:16px; font-weight:bold; color:#1e293b; margin-bottom:10px;">
            <img src="https://img.icons8.com/ios/48/1e293b/bulleted-list.png" style="width:22px; vertical-align:middle; margin-right:8px;">
            ¿Qué debes hacer?
        </div>

        <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:12px; border:1px solid #e2e8f0;">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="width:40px; vertical-align:top;">
                        <div style="background:linear-gradient(135deg,#ef4444,#dc2626); border-radius:50%; width:34px; height:34px; color:white; font-weight:700; font-size:16px; text-align:center; line-height:34px;">1</div>
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#1e293b; font-size:14px; font-weight:600; margin-bottom:3px;">Abre la tarea en el tablero</div>
                        <div style="color:#718096; font-size:13px; line-height:1.5;">Usa el botón de abajo para ir directamente al tablero del grupo.</div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:12px; border:1px solid #e2e8f0;">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="width:40px; vertical-align:top;">
                        <div style="background:linear-gradient(135deg,#ef4444,#dc2626); border-radius:50%; width:34px; height:34px; color:white; font-weight:700; font-size:16px; text-align:center; line-height:34px;">2</div>
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#1e293b; font-size:14px; font-weight:600; margin-bottom:3px;">Actualiza la fecha de vencimiento</div>
                        <div style="color:#718096; font-size:13px; line-height:1.5;">Entra al detalle de la tarea y establece una nueva fecha de vencimiento.</div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:25px; border:1px solid #e2e8f0;">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="width:40px; vertical-align:top;">
                        <div style="background:linear-gradient(135deg,#ef4444,#dc2626); border-radius:50%; width:34px; height:34px; color:white; font-weight:700; font-size:16px; text-align:center; line-height:34px;">3</div>
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#1e293b; font-size:14px; font-weight:600; margin-bottom:3px;">El miembro será notificado automáticamente</div>
                        <div style="color:#718096; font-size:13px; line-height:1.5;">Al guardar la nueva fecha, el sistema enviará un correo a <strong>{{ $nombreSolicitante }}</strong> informando que puede continuar trabajando.</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- BOTÓN -->
        <div style="text-align:center; margin-bottom:40px;">
            <a href="{{ $urlTarea }}"
               style="background:linear-gradient(135deg,#ef4444,#dc2626); padding:14px 32px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(239,68,68,0.5); display:inline-block;">
                <img src="https://img.icons8.com/ios/48/ffffff/external-link.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                Ir al tablero y reactivar tarea
            </a>
        </div>

    </div>

    <!-- FOOTER -->
    <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
        Saludos cordiales,<br>
        <strong style="color:#dc2626;">Equipo de Baby Ballet Marbet®</strong><br><br>
        © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
    </div>

</div>
</div>

</body>
</html>