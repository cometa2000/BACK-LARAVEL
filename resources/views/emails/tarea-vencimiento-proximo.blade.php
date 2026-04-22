<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
<div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

    <!-- HEADER -->
    <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg, #ff9800, #f57c00);">
        <img src="https://img.icons8.com/ios/80/ffffff/alarm.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
        <div style="font-size:28px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
            Tarea Próxima a Vencer
        </div>
        <div style="color:rgba(255,255,255,0.9); font-size:15px; margin-top:6px;">
            @if($diasRestantes == 1)
                Esta tarea vence mañana
            @elseif($diasRestantes == 0)
                Esta tarea vence hoy
            @else
                Esta tarea vence en {{ $diasRestantes }} días
            @endif
        </div>
    </div>

    <div style="padding:35px 35px 10px 35px;">

        <!-- SALUDO -->
        <div style="font-size:16px; color:#1e293b; line-height:1.7; margin-bottom:25px;">
            Hola <strong style="color:#f57c00;">{{ $usuario->name }}</strong>,<br><br>
            Te recordamos que la siguiente tarea está próxima a su fecha de vencimiento. Asegúrate de completarla a tiempo.
        </div>

        <!-- TASK INFO -->
        <div style="font-size:18px; font-weight:bold; color:#1e293b; margin-bottom:8px;">
            <img src="https://img.icons8.com/ios/48/f57c00/note.png" style="width:24px; vertical-align:middle; margin-right:8px;">
            Información de la tarea
        </div>
        <div style="background:#fff3e0; border-radius:12px; padding:22px; border:1px solid #ffcc80; margin-bottom:22px;">
            <div style="color:#1e293b; font-size:20px; font-weight:700; margin-bottom:12px;">
                {{ $tarea->name }}
            </div>
            @if($tarea->description)
            <div style="color:#4a5568; font-size:14px; line-height:1.6; margin-bottom:15px;">
                <strong>Descripción:</strong><br>
                {{ $tarea->description }}
            </div>
            @endif

            <!-- Fecha de vencimiento -->
            <div style="background:#ffffff; border-radius:10px; padding:15px; margin-bottom:15px; border:1px solid #ffe0b2;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:48%; padding-right:2%; border-right:1px solid #ffe0b2;">
                            <div style="color:#795548; font-size:12px; margin-bottom:4px;">
                                <img src="https://img.icons8.com/ios/48/f57c00/calendar--v1.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                                Fecha de vencimiento
                            </div>
                            <div style="color:#1e293b; font-size:14px; font-weight:700;">
                                {{ \Carbon\Carbon::parse($tarea->due_date)->format('d/m/Y') }}
                            </div>
                        </td>
                        <td style="width:48%; padding-left:2%;">
                            <div style="color:#795548; font-size:12px; margin-bottom:4px;">
                                <img src="https://img.icons8.com/ios/48/f57c00/time.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                                Tiempo restante
                            </div>
                            <div style="color:#e65100; font-size:14px; font-weight:700;">
                                @if($diasRestantes == 0)
                                    Vence hoy
                                @elseif($diasRestantes == 1)
                                    Vence mañana
                                @else
                                    {{ $diasRestantes }} días
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- BADGE de estado -->
            <div style="background:linear-gradient(135deg, #ff9800, #f57c00); border-radius:8px; padding:12px; text-align:center;">
                <img src="https://img.icons8.com/ios/48/ffffff/alarm.png" style="width:20px; vertical-align:middle; margin-right:8px;">
                <span style="color:#ffffff; font-size:15px; font-weight:700; vertical-align:middle;">PRÓXIMA A VENCER</span>
            </div>
        </div>

        <!-- CONSEJO -->
        <div style="background:#fef3c7; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #f59e0b;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top; width:32px;">
                        <img src="https://img.icons8.com/ios/48/d97706/idea.png" style="width:24px;">
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#78350f; font-size:14px; line-height:1.6;">
                            <strong>Consejo:</strong> Revisa el progreso de la tarea y asegúrate de completarla antes de la fecha límite. Si necesitas más tiempo, considera actualizar la fecha de vencimiento con tu equipo.
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- BOTÓN CTA -->
        <div style="text-align:center; margin-bottom:40px;">
            <a href="https://crm-angular.preubasbbm.com/tasks/tareas/tablero/{{ $tarea->grupo_id }}"
               style="background:linear-gradient(135deg, #ff9800, #f57c00); padding:14px 32px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(255,152,0,0.5); display:inline-block;">
                <img src="https://img.icons8.com/ios/48/ffffff/external-link.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                Ver tarea completa
            </a>
        </div>

    </div>

    <!-- FOOTER -->
    <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
        Saludos cordiales,<br>
        <strong style="color:#f57c00;">Equipo de Baby Ballet Marbet®</strong><br><br>
        © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
    </div>

</div>
</div>

</body>
</html>