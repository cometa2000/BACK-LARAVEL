<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
    <div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

        <!-- HEADER -->
        <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg,#7c3aed,#a855f7);">
            <img src="https://img.icons8.com/ios/80/ffffff/add-user-group-man-man.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                Miembros asignados
            </div>
            <div style="color:rgba(255,255,255,0.85); font-size:15px; margin-top:6px;">
                Confirmación de asignación en checklist
            </div>
        </div>

        <div style="padding:35px 35px 10px 35px;">

            <!-- SALUDO -->
            <div style="font-size:16px; color:#1e293b; line-height:1.6; margin-bottom:25px;">
                Hola <strong style="color:#7c3aed;">{{ $nombreAsignador }}</strong>,<br><br>
                Asignaste
                @if($cantidadUsuarios === 1)
                    a <strong style="color:#7c3aed;">{{ $usuariosAsignados[0]['name'] }}</strong>
                @else
                    a <strong style="color:#7c3aed;">{{ $cantidadUsuarios }} colaboradores</strong>
                @endif
                al elemento <strong>{{ $nombreItem }}</strong> del checklist <strong>{{ $nombreChecklist }}</strong>.
            </div>

            <!-- CONTEXTO -->
            <div style="font-size:17px; font-weight:bold; color:#1e293b; margin-bottom:10px;">
                <img src="https://img.icons8.com/ios/48/7c3aed/note.png" style="width:22px; vertical-align:middle; margin-right:8px;">
                Elemento del checklist
            </div>
            <div style="background:#f5f3ff; border-radius:12px; padding:20px; border:1px solid #ddd6fe; margin-bottom:22px; line-height:1.7;">
                <strong style="color:#1e293b;">Elemento:</strong> {{ $nombreItem }}<br>
                <strong style="color:#1e293b;">Checklist:</strong> {{ $nombreChecklist }}<br>
                <strong style="color:#1e293b;">Tarea:</strong> {{ $nombreTarea }}<br>
                <strong style="color:#1e293b;">Grupo:</strong> {{ $nombreGrupo }}
            </div>

            <!-- LISTA DE ASIGNADOS -->
            <div style="font-size:17px; font-weight:bold; color:#1e293b; margin-bottom:10px;">
                <img src="https://img.icons8.com/ios/48/7c3aed/conference-call.png" style="width:22px; vertical-align:middle; margin-right:8px;">
                @if($cantidadUsuarios === 1) Colaborador asignado @else Colaboradores asignados @endif
            </div>

            @foreach($usuariosAsignados as $usuario)
            <div style="background:#f9f7ff; border-radius:12px; padding:16px; margin-bottom:10px; border-left:4px solid #7c3aed;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:36px; vertical-align:middle;">
                            <img src="https://img.icons8.com/ios/48/7c3aed/user.png" style="width:28px;">
                        </td>
                        <td style="vertical-align:middle; padding-left:10px;">
                            <div style="color:#1e293b; font-size:15px; font-weight:600;">{{ $usuario['name'] }}</div>
                            <div style="color:#718096; font-size:13px;">{{ $usuario['email'] }}</div>
                        </td>
                    </tr>
                </table>
            </div>
            @endforeach

            <!-- INFO -->
            <div style="background:#ede9fe; border-radius:12px; padding:18px; margin-top:22px; margin-bottom:22px; border-left:4px solid #7c3aed;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:30px;">
                            <img src="https://img.icons8.com/ios/48/4c1d95/info.png" style="width:22px;">
                        </td>
                        <td style="vertical-align:top; padding-left:10px;">
                            <div style="color:#4c1d95; font-size:14px; line-height:1.6;">
                                @if($cantidadUsuarios === 1)
                                    Este colaborador recibirá una notificación informándole sobre su asignación.
                                @else
                                    Estos {{ $cantidadUsuarios }} colaboradores recibirán una notificación informándoles sobre su asignación.
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- CTA -->
            <div style="text-align:center; margin:10px 0 45px;">
                <a href="{{ $urlTablero }}"
                   style="background:linear-gradient(135deg,#7c3aed,#a855f7); padding:14px 28px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(124,58,237,0.5); display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/ffffff/visible.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                    Ver tablero del grupo
                </a>
            </div>

        </div>

        <!-- FOOTER -->
        <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
            Saludos cordiales,<br>
            <strong style="color:#7c3aed;">Equipo de Baby Ballet Marbet®</strong><br><br>
            © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
        </div>

    </div>
</div>
</body>
</html>