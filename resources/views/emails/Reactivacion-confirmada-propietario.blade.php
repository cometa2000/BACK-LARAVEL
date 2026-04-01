<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
<div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

    <!-- HEADER -->
    <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
        <img src="https://img.icons8.com/ios/80/ffffff/restart.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
        <div style="font-size:28px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
            ¡Tarea reactivada exitosamente!
        </div>
        <div style="color:#ede9fe; font-size:15px; margin-top:6px;">
            Tu grupo ya puede continuar con las actividades
        </div>
    </div>

    <div style="padding:35px 35px 10px 35px;">

        <!-- SALUDO -->
        <div style="font-size:16px; color:#1e293b; line-height:1.7; margin-bottom:25px;">
            Hola <strong style="color:#6d28d9;">{{ $nombrePropietario }}</strong>,<br><br>
            Confirmamos que has reactivado exitosamente la tarea en el grupo
            <strong>{{ $nombreGrupo }}</strong>.
            @if($totalMiembros > 0)
                Los miembros asignados ya fueron notificados y pueden continuar trabajando en ella.
            @else
                La tarea no tiene miembros asignados actualmente.
            @endif
        </div>

        <!-- ✅ BANNER DE ACCIÓN REALIZADA -->
        @if($accion === 'eliminada')
        <div style="background:#f0f9ff; border-radius:12px; padding:16px 20px; margin-bottom:22px; border-left:4px solid #0ea5e9;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top; width:32px;">
                        <img src="https://img.icons8.com/ios/48/0ea5e9/delete-sign.png" style="width:22px;">
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#0c4a6e; font-size:14px; line-height:1.5;">
                            <strong>Acción realizada:</strong> Eliminaste la fecha de vencimiento de la tarea.
                            La tarea ya no tiene fecha límite y los miembros pueden trabajar sin restricciones de tiempo.
                            Si esto fue un error, puedes asignar una nueva fecha desde el tablero.
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        @else
        <div style="background:#f5f3ff; border-radius:12px; padding:16px 20px; margin-bottom:22px; border-left:4px solid #8b5cf6;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top; width:32px;">
                        <img src="https://img.icons8.com/ios/48/6d28d9/calendar--v1.png" style="width:22px;">
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#3b0764; font-size:14px; line-height:1.5;">
                            <strong>Acción realizada:</strong> Actualizaste la fecha de vencimiento de la tarea
                            a <strong>{{ $nuevaFecha }}</strong>. Los miembros fueron notificados con la nueva fecha límite.
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        <!-- TARJETA DE CONFIRMACIÓN -->
        <div style="font-size:16px; font-weight:bold; color:#1e293b; margin-bottom:10px;">
            <img src="https://img.icons8.com/ios/48/6d28d9/note.png" style="width:22px; vertical-align:middle; margin-right:8px;">
            Resumen de la reactivación
        </div>
        <div style="background:#f5f3ff; border-radius:12px; padding:22px; border:1px solid #ddd6fe; margin-bottom:25px;">
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="color:#4c1d95; font-size:13px; font-weight:600; width:45%; padding-bottom:12px; vertical-align:top;">Tarea reactivada:</td>
                    <td style="color:#1e293b; font-size:15px; font-weight:700; padding-bottom:12px;">{{ $nombreTarea }}</td>
                </tr>
                <tr>
                    <td style="color:#4c1d95; font-size:13px; font-weight:600; padding-bottom:12px; vertical-align:top;">Grupo:</td>
                    <td style="color:#1e293b; font-size:14px; padding-bottom:12px;">{{ $nombreGrupo }}</td>
                </tr>
                <tr>
                    <td style="color:#4c1d95; font-size:13px; font-weight:600; padding-bottom:12px; vertical-align:top;">Acción realizada:</td>
                    <td style="padding-bottom:12px;">
                        @if($accion === 'eliminada')
                            <span style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:700;">
                                Fecha eliminada
                            </span>
                        @else
                            <span style="background:#ede9fe; color:#6d28d9; border:1px solid #ddd6fe; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:700;">
                                Fecha actualizada
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="color:#4c1d95; font-size:13px; font-weight:600; padding-bottom:12px; vertical-align:top;">Nueva fecha de vencimiento:</td>
                    <td style="padding-bottom:12px;">
                        @if($nuevaFecha === 'Sin fecha asignada')
                            <span style="background:#f3f4f6; color:#6b7280; border:1px solid #d1d5db; border-radius:6px; padding:4px 12px; font-size:13px; font-weight:600;">
                                Sin fecha asignada
                            </span>
                        @else
                            <span style="background:#ede9fe; color:#6d28d9; border:1px solid #ddd6fe; border-radius:6px; padding:4px 12px; font-size:14px; font-weight:700;">
                                {{ $nuevaFecha }}
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="color:#4c1d95; font-size:13px; font-weight:600; vertical-align:top;">Miembros notificados:</td>
                    <td>
                        @if($totalMiembros > 0)
                            <span style="background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; border-radius:6px; padding:4px 12px; font-size:13px; font-weight:700;">
                                ✓ {{ $totalMiembros }} miembro{{ $totalMiembros > 1 ? 's' : '' }} notificado{{ $totalMiembros > 1 ? 's' : '' }}
                            </span>
                        @else
                            <span style="color:#6b7280; font-size:13px;">Sin miembros asignados</span>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- BADGE -->
            <div style="margin-top:18px; background:linear-gradient(135deg,#8b5cf6,#6d28d9); border-radius:8px; padding:12px; text-align:center;">
                <img src="https://img.icons8.com/ios/48/ffffff/checkmark.png" style="width:20px; vertical-align:middle; margin-right:8px;">
                <span style="color:#ffffff; font-size:15px; font-weight:700; vertical-align:middle;">TAREA REACTIVADA — EN PROGRESO</span>
            </div>
        </div>

        <!-- AVISO SI ELIMINÓ LA FECHA (por si fue un error) -->
        @if($accion === 'eliminada')
        <div style="background:#fef3c7; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #f59e0b;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top; width:32px;">
                        <img src="https://img.icons8.com/ios/48/d97706/idea.png" style="width:24px;">
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#78350f; font-size:14px; line-height:1.6;">
                            <strong>¿Fue un error?</strong> Si eliminaste la fecha sin querer, puedes entrar a la tarea
                            y asignar una nueva fecha de vencimiento desde el tablero del grupo en cualquier momento.
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        <!-- BOTÓN -->
        <div style="text-align:center; margin-bottom:40px;">
            <a href="{{ $urlTarea }}"
               style="background:linear-gradient(135deg,#8b5cf6,#6d28d9); padding:14px 32px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(139,92,246,0.5); display:inline-block;">
                <img src="https://img.icons8.com/ios/48/ffffff/external-link.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                Ver tablero del grupo
            </a>
        </div>

    </div>

    <!-- FOOTER -->
    <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
        Saludos cordiales,<br>
        <strong style="color:#6d28d9;">Equipo de Baby Ballet Marbet®</strong><br><br>
        © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
    </div>

</div>
</div>

</body>
</html>