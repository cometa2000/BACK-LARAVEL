<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:#eceffc; font-family: Arial, Helvetica, sans-serif;">

<div style="width:100%; padding:40px 0;">

    <div style="width:90%; max-width:700px; margin:auto; background:#ffffff; border-radius:18px; padding:0; overflow:hidden; box-shadow:0 15px 45px rgba(0,0,0,0.18);">

        <!-- HEADER GRADIENT -->
        <div style="text-align:center; padding:50px 20px; background: linear-gradient(135deg, #6a5af9, #8a2be2);">
            <img src="https://img.icons8.com/ios/80/ffffff/clipboard.png" style="width:80px; filter:drop-shadow(0 3px 5px rgba(0,0,0,.25));">
            <div style="font-size:30px; font-weight:bold; color:white; margin-top:14px; letter-spacing:0.5px;">
                Nueva tarea asignada
            </div>
            <div style="color:#e8e7ff; font-size:15px; margin-top:6px;">
                ¡Tienes una responsabilidad importante por completar!
            </div>
        </div>

        <div style="padding:35px 35px 10px 35px;">

            <!-- SECTION -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                <img src="https://img.icons8.com/ios/48/6a5af9/note.png" style="width:26px; margin-right:10px;">
                Información de la tarea
            </div>
            <div style="background:#eef2ff; border-radius:12px; padding:20px; border:1px solid #d9ddff; margin-bottom:22px; line-height:1.6;">
                <strong style="color:#1e293b;">Título:</strong> {{ $tarea->nombre }}<br>
                <strong style="color:#1e293b;">Descripción:</strong> {{ $tarea->descripcion ?? 'Sin descripción' }}
            </div>

            <!-- FECHA -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                <img src="https://img.icons8.com/ios/48/6a5af9/calendar--v1.png" style="width:26px; margin-right:10px;">
                Fecha límite
            </div>
            <div style="background:#eef2ff; border-radius:12px; padding:20px; border:1px solid #d9ddff; margin-bottom:22px; line-height:1.6;">
                {{ $tarea->fecha_entrega ? $tarea->fecha_entrega->format('d/m/Y') : 'No establecida' }}
            </div>

            <!-- PRIORIDAD -->
            @if($tarea->prioridad)
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                <img src="https://img.icons8.com/ios/48/6a5af9/pin.png" style="width:26px; margin-right:10px;">
                Prioridad
            </div>
            <div style="background:#eef2ff; border-radius:12px; padding:20px; border:1px solid #d9ddff; margin-bottom:22px; line-height:1.6;">
                {{ ucfirst($tarea->prioridad) }}
            </div>
            @endif

            <!-- GRUPO / LISTA -->
            <div style="font-size:18px; font-weight:bold; color:#1e293b; display:flex; align-items:center; margin-bottom:8px;">
                <img src="https://img.icons8.com/ios/48/6a5af9/goal.png" style="width:26px; margin-right:10px;">
                Grupo / Lista
            </div>
            <div style="background:#eef2ff; border-radius:12px; padding:20px; border:1px solid #d9ddff; margin-bottom:30px; line-height:1.6;">
                <strong style="color:#1e293b;">Grupo:</strong> {{ $grupo->nombre }}<br>
                <strong style="color:#1e293b;">Lista:</strong> {{ $lista->nombre }}
            </div>

            <!-- BUTTON CTA -->
            <div style="text-align:center; margin-top:10px; margin-bottom:50px;">
                <a href="{{ env('APP_URL') }}/tareas/{{ $tarea->id }}" 
                   style="background:#6a5af9; padding:14px 28px; border-radius:8px; color:white; font-weight:bold; text-decoration:none; box-shadow:0 4px 12px rgba(106,90,249,0.6); display:inline-block;">
                    Ver tarea en el sistema
                </a>
            </div>

        </div>

        <!-- FOOTER -->
        <div style="text-align:center; padding:25px; background:#f6f6fb; font-size:13px; color:#5f6575;">
            Sistema de Gestión de Tareas y Proyectos<br>
            <strong style="color:#6a5af9;">Trabajando juntos hacia el éxito</strong><br><br>
            © {{ date('Y') }} International Dancing Corporation S.A. de C.V.
        </div>

    </div>

</div>

</body>
</html>
