<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
    <div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

        <!-- HEADER — rojo si vencido, ámbar si próximo -->
        @if($esVencido)
        <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg,#dc2626,#b91c1c);">
            <img src="https://img.icons8.com/ios/80/ffffff/expired.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                Elemento vencido
            </div>
            <div style="color:rgba(255,255,255,0.85); font-size:15px; margin-top:6px;">
                La fecha límite ya ha pasado
            </div>
        </div>
        @else
        <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg,#f59e0b,#d97706);">
            <img src="https://img.icons8.com/ios/80/ffffff/future.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                 Vencimiento próximo
            </div>
            <div style="color:rgba(255,255,255,0.88); font-size:15px; margin-top:6px;">
                @if($diasRestantes === 0)
                    ¡Vence hoy!
                @else
                    {{ $diasRestantes }} día(s) restante(s)
                @endif
            </div>
        </div>
        @endif

        <div style="padding:35px 35px 10px 35px;">

            <!-- SALUDO -->
            <div style="font-size:16px; color:#1e293b; line-height:1.6; margin-bottom:25px;">
                Hola <strong style="color:{{ $esVencido ? '#dc2626' : '#d97706' }};">{{ $nombreUsuario }}</strong>,<br><br>
                @if($esVencido)
                    El elemento <strong>{{ $nombreItem }}</strong> al que estás asignado <strong style="color:#dc2626;">ya venció</strong>. Por favor, revísalo a la brevedad.
                @elseif($diasRestantes === 0)
                    El elemento <strong>{{ $nombreItem }}</strong> al que estás asignado <strong style="color:#d97706;">vence hoy</strong>. ¡Asegúrate de completarlo!
                @else
                    El elemento <strong>{{ $nombreItem }}</strong> al que estás asignado vence en <strong style="color:#d97706;">{{ $diasRestantes }} día(s)</strong>. Planifica con anticipación.
                @endif
            </div>

            <!-- BADGE DE URGENCIA -->
            <div style="text-align:center; margin-bottom:22px;">
                @if($esVencido)
                <div style="background:#fef2f2; border-radius:12px; padding:16px; border:2px solid #fca5a5; display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/dc2626/high-priority.png" style="width:20px; vertical-align:middle; margin-right:8px;">
                    <span style="color:#dc2626; font-size:16px; font-weight:700; vertical-align:middle;">VENCIDO</span>
                </div>
                @elseif($diasRestantes === 0)
                <div style="background:#fffbeb; border-radius:12px; padding:16px; border:2px solid #fcd34d; display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/d97706/alarm.png" style="width:20px; vertical-align:middle; margin-right:8px;">
                    <span style="color:#d97706; font-size:16px; font-weight:700; vertical-align:middle;">VENCE HOY</span>
                </div>
                @else
                <div style="background:#fffbeb; border-radius:12px; padding:16px; border:2px solid #fcd34d; display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/d97706/timer.png" style="width:20px; vertical-align:middle; margin-right:8px;">
                    <span style="color:#d97706; font-size:16px; font-weight:700; vertical-align:middle;">{{ $diasRestantes }} DÍA(S) RESTANTE(S)</span>
                </div>
                @endif
            </div>

            <!-- DETALLE DEL ELEMENTO -->
            <div style="font-size:17px; font-weight:bold; color:#1e293b; margin-bottom:10px;">
                <img src="https://img.icons8.com/ios/48/{{ $esVencido ? 'dc2626' : 'd97706' }}/checked-checkbox.png" style="width:22px; vertical-align:middle; margin-right:8px;">
                Detalles del elemento
            </div>
            <div style="background:{{ $esVencido ? '#fef2f2' : '#fffbeb' }}; border-radius:12px; padding:20px; border:1px solid {{ $esVencido ? '#fca5a5' : '#fcd34d' }}; margin-bottom:22px; line-height:1.8;">
                <strong style="color:#1e293b;">Elemento:</strong> {{ $nombreItem }}<br>
                <strong style="color:#1e293b;">Checklist:</strong> {{ $nombreChecklist }}<br>
                <strong style="color:#1e293b;">Tarea:</strong> {{ $nombreTarea }}<br>
                <strong style="color:#1e293b;">Grupo:</strong> {{ $nombreGrupo }}<br>
                <strong style="color:#1e293b;">Fecha límite:</strong>
                <span style="color:{{ $esVencido ? '#dc2626' : '#d97706' }}; font-weight:700;">
                    {{ \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y') }}
                </span>
            </div>

            <!-- ALERTA -->
            @if($esVencido)
            <div style="background:#fef2f2; border-radius:12px; padding:18px; margin-bottom:22px; border-left:4px solid #dc2626;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:30px;">
                            <img src="https://img.icons8.com/ios/48/dc2626/error.png" style="width:22px;">
                        </td>
                        <td style="vertical-align:top; padding-left:10px;">
                            <div style="color:#7f1d1d; font-size:14px; line-height:1.6;">
                                <strong>Acción requerida:</strong> Este elemento está vencido. Completa la tarea o contacta al responsable del grupo para informarle la situación.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            @else
            <div style="background:#fffbeb; border-radius:12px; padding:18px; margin-bottom:22px; border-left:4px solid #f59e0b;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:30px;">
                            <img src="https://img.icons8.com/ios/48/d97706/idea.png" style="width:22px;">
                        </td>
                        <td style="vertical-align:top; padding-left:10px;">
                            <div style="color:#78350f; font-size:14px; line-height:1.6;">
                                <strong>Consejo:</strong> Organiza tu tiempo para completar este elemento antes de la fecha límite. Una vez completado, márcalo en el sistema.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            @endif

            <!-- CTA -->
            <div style="text-align:center; margin:10px 0 45px;">
                <a href="{{ $urlTablero }}"
                   style="background:{{ $esVencido ? 'linear-gradient(135deg,#dc2626,#b91c1c)' : 'linear-gradient(135deg,#f59e0b,#d97706)' }}; padding:14px 28px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px {{ $esVencido ? 'rgba(220,38,38,0.5)' : 'rgba(245,158,11,0.5)' }}; display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/ffffff/external-link.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                    Ir al tablero del grupo
                </a>
            </div>

        </div>

        <!-- FOOTER -->
        <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
            Saludos cordiales,<br>
            <strong style="color:{{ $esVencido ? '#dc2626' : '#d97706' }};">Equipo de Baby Ballet Marbet®</strong><br><br>
            © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
        </div>

    </div>
</div>
</body>
</html>