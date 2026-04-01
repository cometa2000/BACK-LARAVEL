<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
<div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

    <!-- HEADER -->
    <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg,#10b981,#059669);">
        <img src="https://img.icons8.com/ios/80/ffffff/restart.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
        <div style="font-size:28px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
            ¡Tarea reactivada!
        </div>
        <div style="color:#d1fae5; font-size:15px; margin-top:6px;">
            Ya puedes continuar trabajando en ella
        </div>
    </div>

    <div style="padding:35px 35px 10px 35px;">

        <!-- SALUDO -->
        <div style="font-size:16px; color:#1e293b; line-height:1.7; margin-bottom:25px;">
            Hola <strong style="color:#059669;">{{ $nombreDestinatario }}</strong>,<br><br>
            Te informamos que <strong style="color:#059669;">{{ $nombrePropietario }}</strong>,
            propietario del grupo <strong>{{ $nombreGrupo }}</strong>, ha reactivado la tarea
            que estaba vencida. Ahora puedes continuar trabajando en ella satisfactoriamente.
        </div>

        <!-- ✅ BANNER DE ACCIÓN REALIZADA -->
        @if($accion === 'eliminada')
        <div style="background:#f0f9ff; border-radius:12px; padding:16px 20px; margin-bottom:22px; border-left:4px solid #0ea5e9; display:flex; align-items:center;">
            <img src="https://img.icons8.com/ios/48/0ea5e9/delete-sign.png" style="width:22px; margin-right:12px; flex-shrink:0;">
            <div style="color:#0c4a6e; font-size:14px; line-height:1.5;">
                <strong>Acción realizada:</strong> El propietario <strong>eliminó la fecha de vencimiento</strong>
                de esta tarea. Ya no hay fecha límite asignada, así que puedes continuar sin restricciones de tiempo.
            </div>
        </div>
        @else
        <div style="background:#f0fdf4; border-radius:12px; padding:16px 20px; margin-bottom:22px; border-left:4px solid #22c55e;">
            <img src="https://img.icons8.com/ios/48/22c55e/calendar--v1.png" style="width:22px; margin-right:12px; vertical-align:middle;">
            <span style="color:#14532d; font-size:14px; line-height:1.5; vertical-align:middle;">
                <strong>Acción realizada:</strong> El propietario <strong>actualizó la fecha de vencimiento</strong>
                de esta tarea a una nueva fecha. Asegúrate de terminarla antes del nuevo límite.
            </span>
        </div>
        @endif

        <!-- TARJETA DE TAREA REACTIVADA -->
        <div style="font-size:16px; font-weight:bold; color:#1e293b; margin-bottom:10px;">
            <img src="https://img.icons8.com/ios/48/059669/note.png" style="width:22px; vertical-align:middle; margin-right:8px;">
            Detalles de la tarea
        </div>
        <div style="background:#f0fdf4; border-radius:12px; padding:22px; border:1px solid #bbf7d0; margin-bottom:25px;">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="color:#065f46; font-size:13px; font-weight:600; width:45%; padding-bottom:10px; vertical-align:top;">Nombre de la tarea:</td>
                    <td style="color:#1e293b; font-size:15px; font-weight:700; padding-bottom:10px;">{{ $nombreTarea }}</td>
                </tr>
                <tr>
                    <td style="color:#065f46; font-size:13px; font-weight:600; padding-bottom:10px; vertical-align:top;">Grupo:</td>
                    <td style="color:#1e293b; font-size:14px; padding-bottom:10px;">{{ $nombreGrupo }}</td>
                </tr>
                <tr>
                    <td style="color:#065f46; font-size:13px; font-weight:600; padding-bottom:10px; vertical-align:top;">Reactivada por:</td>
                    <td style="color:#1e293b; font-size:14px; font-weight:600; padding-bottom:10px;">
                        <img src="https://img.icons8.com/ios/48/059669/crown.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                        {{ $nombrePropietario }}
                    </td>
                </tr>
                <tr>
                    <td style="color:#065f46; font-size:13px; font-weight:600; padding-bottom:10px; vertical-align:top;">Acción del propietario:</td>
                    <td style="padding-bottom:10px;">
                        @if($accion === 'eliminada')
                            <span style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:700;">
                                Fecha eliminada
                            </span>
                        @else
                            <span style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:700;">
                                Fecha actualizada
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="color:#065f46; font-size:13px; font-weight:600; vertical-align:top;">Nueva fecha de vencimiento:</td>
                    <td>
                        @if($nuevaFechaVencimiento === 'Sin fecha asignada')
                            <span style="background:#f3f4f6; color:#6b7280; border:1px solid #d1d5db; border-radius:6px; padding:4px 12px; font-size:13px; font-weight:600;">
                                Sin fecha asignada
                            </span>
                        @else
                            <span style="background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; border-radius:6px; padding:4px 12px; font-size:14px; font-weight:700;">
                                {{ $nuevaFechaVencimiento }}
                            </span>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- BADGE -->
            <div style="margin-top:18px; background:linear-gradient(135deg,#10b981,#059669); border-radius:8px; padding:12px; text-align:center;">
                <img src="https://img.icons8.com/ios/48/ffffff/checkmark.png" style="width:20px; vertical-align:middle; margin-right:8px;">
                <span style="color:#ffffff; font-size:15px; font-weight:700; vertical-align:middle;">TAREA REACTIVADA — EN PROGRESO</span>
            </div>
        </div>

        <!-- AVISO -->
        @if($nuevaFechaVencimiento !== 'Sin fecha asignada')
        <div style="background:#fef3c7; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #f59e0b;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top; width:32px;">
                        <img src="https://img.icons8.com/ios/48/d97706/high-priority.png" style="width:24px;">
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#78350f; font-size:14px; line-height:1.6;">
                            <strong>Importante:</strong> Completa la tarea antes del
                            <strong>{{ $nuevaFechaVencimiento }}</strong>.
                            Si necesitas más tiempo, comunícate con el propietario del grupo.
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        @else
        <div style="background:#f0f9ff; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #0ea5e9;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top; width:32px;">
                        <img src="https://img.icons8.com/ios/48/0ea5e9/info.png" style="width:24px;">
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#0c4a6e; font-size:14px; line-height:1.6;">
                            <strong>Sin fecha límite:</strong> Esta tarea no tiene fecha de vencimiento asignada.
                            Trabaja en ella con responsabilidad y comunícate con el propietario si necesitas orientación.
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        <!-- BOTÓN -->
        <div style="text-align:center; margin-bottom:40px;">
            <a href="{{ $urlTarea }}"
               style="background:linear-gradient(135deg,#10b981,#059669); padding:14px 32px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(16,185,129,0.5); display:inline-block;">
                <img src="https://img.icons8.com/ios/48/ffffff/external-link.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                Ir a la tarea ahora
            </a>
        </div>

    </div>

    <!-- FOOTER -->
    <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
        Saludos cordiales,<br>
        <strong style="color:#059669;">Equipo de Baby Ballet Marbet®</strong><br><br>
        © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
    </div>

</div>
</div>

</body>
</html>