<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:#eceffc; font-family: Arial, Helvetica, sans-serif;">

<div style="width:100%; padding:40px 0;">

    <div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; padding:0; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

        <!-- HEADER GRADIENT -->
        <div style="text-align:center; padding:50px 20px; background: linear-gradient(135deg, #10b981, #059669);">
            <img src="https://img.icons8.com/ios/80/ffffff/checkmark.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                ¡Tarea Completada!
            </div>
            <div style="color:#d1fae5; font-size:15px; margin-top:6px;">
                Una tarea ha sido marcada como completada
            </div>
        </div>

        <div style="padding:35px 35px 10px 35px;">

            <!-- GREETING -->
            <div style="font-size:16px; color:#1e293b; line-height:1.6; margin-bottom:25px;">
                Hola <strong style="color:#10b981;">{{ $nombreUsuario }}</strong>,<br><br>
                @if($esCreador)
                    <strong style="color:#10b981;">{{ $nombreCompletador }}</strong> ha completado la tarea que creaste.
                @else
                    <strong style="color:#10b981;">{{ $nombreCompletador }}</strong> ha completado una tarea en la que estás asignado.
                @endif
            </div>

            <!-- TASK INFO -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                <img src="https://img.icons8.com/ios/48/10b981/note.png" style="width:26px; margin-right:10px;">
                Tarea Completada
            </div>
            <div style="background:#f0fdf4; border-radius:12px; padding:20px; border:1px solid #bbf7d0; margin-bottom:22px;">
                <div style="color:#1e293b; font-size:20px; font-weight:700; margin-bottom:15px;">
                    {{ $tarea->name }}
                </div>
                @if($tarea->description)
                <div style="color:#4a5568; font-size:14px; line-height:1.6; margin-bottom:15px;">
                    <strong>Descripción:</strong><br>
                    {{ $tarea->description }}
                </div>
                @endif

                <!-- DETAILS -->
                <div style="background:#ffffff; border-radius:10px; padding:15px; margin-bottom:15px; border:1px solid #dcfce7;">
                    <table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td style="width:48%; padding-right:2%; border-right:1px solid #dcfce7;">
                                <div style="color:#718096; font-size:12px; margin-bottom:4px;">
                                    <img src="https://img.icons8.com/ios/48/10b981/folder-invoices.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                                    Grupo
                                </div>
                                <div style="color:#1e293b; font-size:14px; font-weight:600;">{{ $grupo->name }}</div>
                            </td>
                            <td style="width:48%; padding-left:2%;">
                                <div style="color:#718096; font-size:12px; margin-bottom:4px;">
                                    <img src="https://img.icons8.com/ios/48/10b981/bulleted-list.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                                    Lista
                                </div>
                                <div style="color:#1e293b; font-size:14px; font-weight:600;">{{ $lista->name }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- STATUS BADGE -->
                <div style="background:linear-gradient(135deg, #10b981, #059669); border-radius:10px; padding:12px; text-align:center;">
                    <img src="https://img.icons8.com/ios/80/ffffff/checkmark.png" style="width:22px; vertical-align:middle; margin-right:8px;">
                    <span style="color:#ffffff; font-size:16px; font-weight:700; vertical-align:middle;">COMPLETADA</span>
                </div>

                <!-- COMPLETED BY -->
                <div style="margin-top:15px; padding-top:15px; border-top:1px solid #dcfce7;">
                    <table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td>
                                <img src="https://img.icons8.com/ios/48/10b981/user.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                                <span style="color:#4a5568; font-size:13px; vertical-align:middle;">
                                    Completada por <strong style="color:#1e293b;">{{ $nombreCompletador }}</strong>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <span style="color:#718096; font-size:12px;">{{ now()->format('d/m/Y H:i') }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- INFO BOX -->
            @if($esCreador)
            <div style="background:#dbeafe; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #3b82f6;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/1e40af/info.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e3a8a; font-size:14px; line-height:1.6;">
                                <strong>Nota:</strong> Como creador de esta tarea, puedes revisar el trabajo completado y verificar que todo esté en orden.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            @else
            <div style="background:#dbeafe; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #3b82f6;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/1e40af/thumb-up.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e3a8a; font-size:14px; line-height:1.6;">
                                <strong>¡Bien hecho!</strong> Esta tarea ha sido completada exitosamente. El equipo avanza según lo planeado.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            @endif

            <!-- BUTTON CTA -->
            <div style="text-align:center; margin-top:10px; margin-bottom:50px;">
                <a href="https://crmbbm.preubasbbm.com/tasks/tareas/tablero/{{ $grupo->id }}" 
                   style="background: linear-gradient(135deg, #10b981, #059669); padding:14px 28px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(16,185,129,0.6); display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/ffffff/visible.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                    Ver Detalles
                </a>
            </div>

        </div>

        <!-- FOOTER -->
        <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
            Saludos cordiales,<br>
            <strong style="color:#10b981;">Equipo de Baby Ballet Marbet®</strong><br><br>
            © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
        </div>

    </div>

</div>

</body>
</html>