<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
<div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

    <!-- HEADER -->
    <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg, #f44336, #d32f2f);">
        <img src="https://img.icons8.com/ios/80/ffffff/cancel.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
        <div style="font-size:28px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
            Tarea Vencida
        </div>
        <div style="color:rgba(255,255,255,0.9); font-size:15px; margin-top:6px;">
            Esta tarea ha superado su fecha de vencimiento
        </div>
    </div>

    <div style="padding:35px 35px 10px 35px;">

        <!-- SALUDO -->
        <div style="font-size:16px; color:#1e293b; line-height:1.7; margin-bottom:25px;">
            Hola <strong style="color:#d32f2f;">{{ $usuario->name }}</strong>,<br><br>
            Te notificamos que la siguiente tarea <strong style="color:#d32f2f;">ha vencido</strong> sin ser completada. Por favor, revisa su estado y toma las acciones necesarias.
        </div>

        <!-- TASK INFO -->
        <div style="font-size:18px; font-weight:bold; color:#1e293b; margin-bottom:8px;">
            <img src="https://img.icons8.com/ios/48/d32f2f/note.png" style="width:24px; vertical-align:middle; margin-right:8px;">
            Información de la tarea
        </div>
        <div style="background:#ffebee; border-radius:12px; padding:22px; border:1px solid #ef9a9a; margin-bottom:22px;">
            <div style="color:#1e293b; font-size:20px; font-weight:700; margin-bottom:12px;">
                {{ $tarea->name }}
            </div>
            @if($tarea->description)
            <div style="color:#4a5568; font-size:14px; line-height:1.6; margin-bottom:15px;">
                <strong>Descripción:</strong><br>
                {{ $tarea->description }}
            </div>
            @endif

            <!-- Fechas y estado -->
            <div style="background:#ffffff; border-radius:10px; padding:15px; margin-bottom:15px; border:1px solid #ffcdd2;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:48%; padding-right:2%; border-right:1px solid #ffcdd2; vertical-align:top;">
                            <div style="color:#b71c1c; font-size:12px; margin-bottom:4px;">
                                <img src="https://img.icons8.com/ios/48/d32f2f/calendar--v1.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                                Fecha de vencimiento
                            </div>
                            <div style="color:#1e293b; font-size:14px; font-weight:700;">
                                {{ \Carbon\Carbon::parse($tarea->due_date)->format('d/m/Y') }}
                            </div>
                        </td>
                        <td style="width:48%; padding-left:2%; vertical-align:top;">
                            <div style="color:#b71c1c; font-size:12px; margin-bottom:4px;">
                                <img src="https://img.icons8.com/ios/48/d32f2f/time.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                                Tiempo transcurrido
                            </div>
                            <div style="color:#c62828; font-size:14px; font-weight:700;">
                                {{ \Carbon\Carbon::parse($tarea->due_date)->diffForHumans() }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Estado actual y prioridad -->
            <div style="background:#ffffff; border-radius:10px; padding:15px; margin-bottom:15px; border:1px solid #ffcdd2;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:48%; padding-right:2%; border-right:1px solid #ffcdd2; vertical-align:top;">
                            <div style="color:#b71c1c; font-size:12px; margin-bottom:6px;">
                                <img src="https://img.icons8.com/ios/48/d32f2f/process.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                                Estado actual
                            </div>
                            <span style="
                                background:{{ $tarea->status == 'completada' ? '#e8f5e9' : ($tarea->status == 'en_progreso' ? '#e3f2fd' : '#f5f5f5') }};
                                color:{{ $tarea->status == 'completada' ? '#2e7d32' : ($tarea->status == 'en_progreso' ? '#1565c0' : '#616161') }};
                                border:1px solid {{ $tarea->status == 'completada' ? '#a5d6a7' : ($tarea->status == 'en_progreso' ? '#90caf9' : '#e0e0e0') }};
                                border-radius:6px; padding:3px 10px; font-size:12px; font-weight:700;">
                                {{ $tarea->status == 'completada' ? 'Completada' : ($tarea->status == 'en_progreso' ? 'En Progreso' : 'Pendiente') }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- BADGE de estado -->
            <div style="background:linear-gradient(135deg, #f44336, #d32f2f); border-radius:8px; padding:12px; text-align:center;">
                <img src="https://img.icons8.com/ios/48/ffffff/cancel.png" style="width:20px; vertical-align:middle; margin-right:8px;">
                <span style="color:#ffffff; font-size:15px; font-weight:700; vertical-align:middle;">TAREA VENCIDA — ACCIÓN REQUERIDA</span>
            </div>
        </div>

        <!-- ALERTA DE ACCIÓN -->
        <div style="background:#fef3c7; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #f59e0b;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:top; width:32px;">
                        <img src="https://img.icons8.com/ios/48/d97706/high-priority.png" style="width:24px;">
                    </td>
                    <td style="vertical-align:top; padding-left:12px;">
                        <div style="color:#78350f; font-size:14px; line-height:1.6;">
                            <strong>Acción requerida:</strong> Esta tarea ha superado su fecha límite. Por favor, toma las medidas necesarias:<br><br>
                            • Si ya está completa, márcala como <strong>"Completada"</strong><br>
                            • Si necesitas más tiempo, actualiza la fecha de vencimiento<br>
                            • Si hay problemas, comunícate con tu equipo
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- BOTÓN CTA -->
        <div style="text-align:center; margin-bottom:40px;">
            <a href="https://crm-angular.preubasbbm.com/tasks/tareas/tablero/{{ $tarea->grupo_id }}"
               style="background:linear-gradient(135deg, #f44336, #d32f2f); padding:14px 32px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(244,67,54,0.5); display:inline-block;">
                <img src="https://img.icons8.com/ios/48/ffffff/external-link.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                Ir a la tarea ahora
            </a>
        </div>

    </div>

    <!-- FOOTER -->
    <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
        Saludos cordiales,<br>
        <strong style="color:#d32f2f;">Equipo de Baby Ballet Marbet®</strong><br><br>
        © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
    </div>

</div>
</div>

</body>
</html>