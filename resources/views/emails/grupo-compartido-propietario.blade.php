<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background: rgb(228, 228, 228); font-family: Arial, Helvetica, sans-serif;">

<div style="width:100%; padding:40px 0;">

    <div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; padding:0; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

        <!-- HEADER GRADIENT -->
        <div style="text-align:center; padding:50px 20px; background: linear-gradient(135deg, #667eea, #764ba2);">
            <img src="https://img.icons8.com/ios/80/ffffff/share.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                Grupo Compartido
            </div>
            <div style="color:#e8e7ff; font-size:15px; margin-top:6px;">
                Confirmación de compartir grupo
            </div>
        </div>

        <div style="padding:35px 35px 10px 35px;">

            <!-- GREETING -->
            <div style="font-size:16px; color:#1e293b; line-height:1.6; margin-bottom:25px;">
                Hola <strong style="color:#667eea;">{{ $nombrePropietario }}</strong>,<br><br>
                Has compartido exitosamente el grupo <strong style="color:#667eea;">{{ $nombreGrupo }}</strong> con 
                @if($cantidadUsuarios === 1)
                    el siguiente colaborador:
                @else
                    los siguientes {{ $cantidadUsuarios }} colaboradores:
                @endif
            </div>

            <!-- SECTION HEADER -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:12px;">
                <img src="https://img.icons8.com/?size=30&id=101316&format.png" style="width:26px; margin-right:10px;">
                Nuevos Miembros
            </div>

            <!-- USERS LIST -->
            @foreach($usuariosCompartidos as $usuario)
            <div style="background:#f8f9fa; border-radius:12px; padding:18px; margin-bottom:12px; border-left:4px solid #667eea;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:40px; vertical-align:middle;">
                            <img src="https://img.icons8.com/ios/48/667eea/user.png" style="width:32px;">
                        </td>
                        <td style="vertical-align:middle; padding-left:12px;">
                            <div style="color:#1e293b; font-size:15px; font-weight:600; margin-bottom:4px;">
                                {{ $usuario['name'] }}
                            </div>
                            <div style="color:#718096; font-size:13px;">
                                {{ $usuario['email'] }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            @endforeach

            <!-- INFO BOX -->
            <div style="background:#dbeafe; border-radius:12px; padding:20px; margin-top:25px; margin-bottom:25px; border-left:4px solid #3b82f6;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/1e40af/info.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e3a8a; font-size:14px; line-height:1.6;">
                                <strong>Importante:</strong>
                                @if($cantidadUsuarios === 1)
                                    Este usuario ahora puede ver y trabajar en las listas y tareas de tu grupo.
                                @else
                                    Estos {{ $cantidadUsuarios }} usuarios ahora pueden ver y trabajar en las listas y tareas de tu grupo.
                                @endif
                                Podrán colaborar contigo en tiempo real.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- BUTTON CTA -->
            <div style="text-align:center; margin-top:10px; margin-bottom:40px;">
                <a href="{{ env('APP_URL') }}/tasks/grupos/list" 
                   style="background: linear-gradient(135deg, #667eea, #764ba2); padding:14px 32px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(102,126,234,0.6); display:inline-block;">
                    <img src="https://img.icons8.com/material-rounded/96/ffffff/login-rounded-right.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                    Ver Mi Grupo
                </a>
            </div>

            <!-- SECURITY WARNING -->
            <div style="background:#fffbeb; border-radius:12px; padding:20px; border-left:4px solid #f59e0b;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/d97706/lock.png" style="width:24px;">
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#78350f; font-size:13px; line-height:1.6;">
                                <strong>Seguridad:</strong> Si no realizaste esta acción, comunícate con el administrador del sistema.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- FOOTER -->
        <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
            Saludos cordiales,<br>
            <strong style="color:#667eea;">Equipo de Baby Ballet Marbet®</strong><br><br>
            © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
        </div>

    </div>

</div>

</body>
</html>