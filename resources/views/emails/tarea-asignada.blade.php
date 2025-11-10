<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background: rgb(228, 228, 228); font-family: Arial, Helvetica, sans-serif;">

<div style="width:100%; padding:40px 0;">

    <div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; padding:0; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

        <!-- HEADER GRADIENT -->
        <div style="text-align:center; padding:50px 20px; background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
            <img src="https://img.icons8.com/ios/80/ffffff/add-user-male.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                Nueva tarea asignada
            </div>
            <div style="color:#e8e7ff; font-size:15px; margin-top:6px;">
                Tienes una nueva responsabilidad
            </div>
        </div>

        <div style="padding:35px 35px 10px 35px;">

            <!-- GREETING -->
            <div style="font-size:16px; color:#1e293b; line-height:1.6; margin-bottom:25px;">
                Hola <strong style="color:#3b82f6;">{{ $nombreUsuario }}</strong>,<br><br>
                <strong style="color:#3b82f6;">{{ $nombreAsignador }}</strong> te ha asignado una nueva tarea. Revisa los detalles a continuación:
            </div>

            <!-- TASK INFO -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                <img src="https://img.icons8.com/ios/48/3b82f6/note.png" style="width:26px; margin-right:10px;">
                Información de la tarea
            </div>
            <div style="background:#f0f9ff; border-radius:12px; padding:20px; border:1px solid #bae6fd; margin-bottom:22px; line-height:1.6;">
                <strong style="color:#1e293b;">Título:</strong> {{ $tarea->name }}<br>
                <strong style="color:#1e293b;">Descripción:</strong> {{ $tarea->description ?? 'Sin descripción' }}
            </div>

            <!-- DETAILS GRID -->
            <table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:22px;">
                <tr>
                    <td style="width:48%; vertical-align:top; padding-right:2%;">
                        <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                            <img src="https://img.icons8.com/ios/48/3b82f6/folder-invoices.png" style="width:26px; margin-right:10px;">
                            Grupo
                        </div>
                        <div style="background:#f0f9ff; border-radius:12px; padding:20px; border:1px solid #bae6fd; margin-bottom:22px;">
                            {{ $grupo->name }}
                        </div>
                    </td>
                    <td style="width:48%; vertical-align:top; padding-left:2%;">
                        <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                            <img src="https://img.icons8.com/ios/48/3b82f6/bulleted-list.png" style="width:26px; margin-right:10px;">
                            Lista
                        </div>
                        <div style="background:#f0f9ff; border-radius:12px; padding:20px; border:1px solid #bae6fd; margin-bottom:22px;">
                            {{ $lista->name }}
                        </div>
                    </td>
                </tr>
            </table>

            @if($tarea->due_date)
            <!-- FECHA -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                <img src="https://img.icons8.com/ios/48/ef4444/calendar--v1.png" style="width:26px; margin-right:10px;">
                Fecha límite
            </div>
            <div style="background:#fef2f2; border-radius:12px; padding:20px; border:1px solid #fecaca; margin-bottom:22px; line-height:1.6;">
                {{ \Carbon\Carbon::parse($tarea->due_date)->format('d/m/Y H:i') }}
            </div>
            @endif

            @if($tarea->priority)
            <!-- PRIORIDAD -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                <img src="https://img.icons8.com/ios/48/f59e0b/flag.png" style="width:26px; margin-right:10px;">
                Prioridad
            </div>
            <div style="background:#fef3c7; border-radius:12px; padding:20px; border:1px solid #fde68a; margin-bottom:30px; line-height:1.6;">
                {{ $tarea->priority == 'high' ? 'Alta' : ($tarea->priority == 'medium' ? 'Media' : 'Baja') }}
            </div>
            @endif

            <!-- REMINDER BOX -->
            <div style="background:#fef3c7; border-radius:12px; padding:20px; margin-bottom:25px; border-left:4px solid #f59e0b;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/d97706/idea.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#78350f; font-size:14px; line-height:1.6;">
                                <strong>Recordatorio:</strong> Revisa los detalles de la tarea y planifica tu trabajo. Si tienes dudas, comunícate con {{ $nombreAsignador }}.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- BUTTON CTA -->
            <div style="text-align:center; margin-top:10px; margin-bottom:50px;">
                <a href="{{ $urlTarea }}" 
                   style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); padding:14px 28px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(59,130,246,0.6); display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/ffffff/external-link.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                    Ver tarea completa
                </a>
            </div>

        </div>

        <!-- FOOTER -->
        <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
            Saludos cordiales,<br>
            <strong style="color:#3b82f6;">Equipo de Baby Ballet Marbet®</strong><br><br>
            © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
        </div>

    </div>

</div>

</body>
</html>