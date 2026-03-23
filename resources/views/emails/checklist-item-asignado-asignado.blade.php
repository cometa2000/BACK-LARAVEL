<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
    <div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

        <!-- HEADER -->
        <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg,#3b82f6,#8b5cf6);">
            <img src="https://img.icons8.com/ios/80/ffffff/add-user-male.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                ¡Tienes una nueva asignación!
            </div>
            <div style="color:rgba(255,255,255,0.85); font-size:15px; margin-top:6px;">
                Se te asignó un elemento de checklist
            </div>
        </div>

        <div style="padding:35px 35px 10px 35px;">

            <!-- SALUDO -->
            <div style="font-size:16px; color:#1e293b; line-height:1.6; margin-bottom:25px;">
                Hola <strong style="color:#3b82f6;">{{ $nombreAsignado }}</strong>,<br><br>
                <strong style="color:#3b82f6;">{{ $nombreAsignador }}</strong> te ha asignado a un elemento del checklist. Revisa los detalles a continuación:
            </div>

            <!-- DETALLE DEL ELEMENTO -->
            <div style="font-size:17px; font-weight:bold; color:#1e293b; margin-bottom:10px;">
                <img src="https://img.icons8.com/ios/48/3b82f6/checked-checkbox.png" style="width:22px; vertical-align:middle; margin-right:8px;">
                Tu elemento asignado
            </div>
            <div style="background:#eff6ff; border-radius:12px; padding:20px; border:1px solid #bfdbfe; margin-bottom:22px;">
                <div style="color:#1e293b; font-size:20px; font-weight:700; margin-bottom:14px;">{{ $nombreItem }}</div>

                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:48%; padding-right:2%; vertical-align:top; border-right:1px solid #bfdbfe;">
                            <div style="color:#718096; font-size:12px; margin-bottom:4px;">
                                <img src="https://img.icons8.com/ios/48/3b82f6/task-planning.png" style="width:14px; vertical-align:middle; margin-right:4px;">
                                Checklist
                            </div>
                            <div style="color:#1e293b; font-size:14px; font-weight:600;">{{ $nombreChecklist }}</div>
                        </td>
                        <td style="width:48%; padding-left:2%; vertical-align:top;">
                            <div style="color:#718096; font-size:12px; margin-bottom:4px;">
                                <img src="https://img.icons8.com/ios/48/3b82f6/note.png" style="width:14px; vertical-align:middle; margin-right:4px;">
                                Tarea
                            </div>
                            <div style="color:#1e293b; font-size:14px; font-weight:600;">{{ $nombreTarea }}</div>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:14px; padding-top:14px; border-top:1px solid #bfdbfe;">
                    <div style="color:#718096; font-size:12px; margin-bottom:4px;">
                        <img src="https://img.icons8.com/ios/48/3b82f6/folder-invoices.png" style="width:14px; vertical-align:middle; margin-right:4px;">
                        Grupo
                    </div>
                    <div style="color:#1e293b; font-size:14px; font-weight:600;">{{ $nombreGrupo }}</div>
                </div>

                @if($fechaVencimiento)
                <div style="margin-top:14px; padding-top:14px; border-top:1px solid #bfdbfe;">
                    <div style="color:#718096; font-size:12px; margin-bottom:4px;">
                        <img src="https://img.icons8.com/ios/48/ef4444/calendar--v1.png" style="width:14px; vertical-align:middle; margin-right:4px;">
                        Fecha límite
                    </div>
                    <div style="color:#dc2626; font-size:14px; font-weight:700;">
                        {{ \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y') }}
                    </div>
                </div>
                @endif
            </div>

            <!-- RECORDATORIO -->
            <div style="background:#fef3c7; border-radius:12px; padding:18px; margin-bottom:22px; border-left:4px solid #f59e0b;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:30px;">
                            <img src="https://img.icons8.com/ios/48/d97706/idea.png" style="width:22px;">
                        </td>
                        <td style="vertical-align:top; padding-left:10px;">
                            <div style="color:#78350f; font-size:14px; line-height:1.6;">
                                <strong>Recuerda:</strong> Marca el elemento como completado una vez que termines. Si tienes dudas, consulta a <strong>{{ $nombreAsignador }}</strong>.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- CTA -->
            <div style="text-align:center; margin:10px 0 45px;">
                <a href="{{ $urlTablero }}"
                   style="background:linear-gradient(135deg,#3b82f6,#8b5cf6); padding:14px 28px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(59,130,246,0.5); display:inline-block;">
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