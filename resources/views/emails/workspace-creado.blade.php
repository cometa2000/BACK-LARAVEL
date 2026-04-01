<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:rgb(228,228,228); font-family:Arial,Helvetica,sans-serif;">

<div style="width:100%; padding:40px 0;">
    <div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

        <!-- HEADER -->
        <div style="text-align:center; padding:50px 20px; background:linear-gradient(135deg, {{ $colorWorkspace }}, #4f46e5);">
            <img src="https://img.icons8.com/?size=100&id=39077&format=png&color=000000" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                ¡Espacio de trabajo creado!
            </div>
            <div style="color:rgba(255,255,255,0.85); font-size:15px; margin-top:6px;">
                Tu nuevo entorno de organización está listo
            </div>
        </div>

        <div style="padding:35px 35px 10px 35px;">

            <!-- SALUDO -->
            <div style="font-size:16px; color:#1e293b; line-height:1.6; margin-bottom:25px;">
                Hola <strong style="color:{{ $colorWorkspace }};">{{ $nombreUsuario }}</strong>,<br><br>
                Tu espacio de trabajo fue creado exitosamente. Desde aquí puedes organizar tus grupos, listas y tareas en un solo lugar.
            </div>

            <!-- TARJETA DEL WORKSPACE -->
            <div style="background:linear-gradient(135deg,#f7fafc,#edf2f7); border-radius:14px; padding:25px; margin-bottom:25px; border:2px solid #e2e8f0; text-align:center;">
                <div style="width:14px; height:14px; background:{{ $colorWorkspace }}; border-radius:50%; display:inline-block; margin-bottom:12px; box-shadow:0 0 0 4px rgba(102,126,234,0.2);"></div>
                <div style="color:#718096; font-size:11px; text-transform:uppercase; letter-spacing:1.2px; font-weight:600; margin-bottom:8px;">
                    Nombre del espacio de trabajo
                </div>
                <div style="color:#1a202c; font-size:26px; font-weight:700; margin-bottom:12px;">
                    {{ $nombreWorkspace }}
                </div>
                @if($descripcionWorkspace)
                <div style="color:#718096; font-size:14px; font-style:italic; margin-bottom:15px;">
                    {{ $descripcionWorkspace }}
                </div>
                @endif
                <div style="background:#ffffff; border-radius:10px; padding:10px 16px; border:1px solid #e2e8f0; display:inline-block;">
                    <img src="https://img.icons8.com/ios/48/48bb78/checkmark.png" style="width:16px; vertical-align:middle; margin-right:6px;">
                    <span style="color:#2f855a; font-size:13px; font-weight:600; vertical-align:middle;">Estado: Activo</span>
                </div>
            </div>

            <!-- PRÓXIMOS PASOS -->
            <div style="font-size:17px; font-weight:bold; color:#1e293b; margin-bottom:14px;">
                <img src="https://img.icons8.com/ios/48/4f46e5/rocket.png" style="width:22px; vertical-align:middle; margin-right:8px;">
                ¿Qué puedes hacer ahora?
            </div>

            @php
                $pasos = [
                    ['num' => '1', 'titulo' => 'Crea grupos', 'desc' => 'Agrupa tus proyectos dentro del workspace para mantenerlos organizados.'],
                    ['num' => '2', 'titulo' => 'Organiza con listas', 'desc' => 'Dentro de cada grupo define listas como "Pendiente", "En progreso" o "Completado".'],
                    ['num' => '3', 'titulo' => 'Agrega tareas', 'desc' => 'Asigna tareas a los miembros de tu equipo con fechas y prioridades.'],
                ];
            @endphp

            @foreach($pasos as $paso)
            <div style="background:#ffffff; border-radius:12px; padding:18px; margin-bottom:12px; border:1px solid #e2e8f0;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="width:40px; vertical-align:top;">
                            <div style="background:{{ $colorWorkspace }}; border-radius:50%; width:36px; height:36px; color:white; font-weight:700; font-size:16px; text-align:center; line-height:36px;">
                                {{ $paso['num'] }}
                            </div>
                        </td>
                        <td style="vertical-align:top; padding-left:12px;">
                            <div style="color:#1e293b; font-size:15px; font-weight:600; margin-bottom:4px;">{{ $paso['titulo'] }}</div>
                            <div style="color:#718096; font-size:13px; line-height:1.5;">{{ $paso['desc'] }}</div>
                        </td>
                    </tr>
                </table>
            </div>
            @endforeach

            <!-- CTA -->
            <div style="text-align:center; margin:30px 0 40px;">
                <a href="https://crm-angular.preubasbbm.com/tasks/workspaces"
                   style="background:linear-gradient(135deg,{{ $colorWorkspace }},#4f46e5); padding:14px 32px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(79,70,229,0.5); display:inline-block;">
                    <img src="https://img.icons8.com/material-rounded/96/ffffff/login-rounded-right.png" style="width:18px; vertical-align:middle; margin-right:8px;">
                    Ir a mis espacios de trabajo
                </a>
            </div>

            <!-- SEGURIDAD -->
            <div style="background:#dbeafe; border-radius:12px; padding:18px; margin-bottom:30px; border-left:4px solid #3b82f6;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:top; width:32px;">
                            <img src="https://img.icons8.com/ios/48/1e40af/security-checked.png" style="width:22px;">
                        </td>
                        <td style="vertical-align:top; padding-left:10px;">
                            <div style="color:#1e3a8a; font-size:13px; line-height:1.6;">
                                <strong>Seguridad:</strong> Si no realizaste esta acción, contacta al administrador inmediatamente.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- FOOTER -->
        <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
            Saludos cordiales,<br>
            <strong style="color:{{ $colorWorkspace }};">Equipo de Baby Ballet Marbet®</strong><br><br>
            © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
        </div>

    </div>
</div>
</body>
</html>