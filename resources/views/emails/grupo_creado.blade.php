@component('mail::message')
{{-- Header con logo --}}
<div style="text-align: center; padding: 20px 0;">
    <img src="https://i.imgur.com/placeholder-logo.png" alt="Logo Empresa" style="max-width: 180px; height: auto;">
    {{-- ⚠️ Reemplaza esta URL con el logo real de tu empresa --}}
</div>

{{-- Título principal --}}
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin: 20px 0;">
    <h1 style="margin: 0; font-size: 28px; font-weight: bold;">
        🎉 ¡Grupo Creado Exitosamente!
    </h1>
</div>

{{-- Mensaje principal --}}
<div style="background-color: #f8f9fa; padding: 25px; border-radius: 10px; margin: 20px 0;">
    <p style="font-size: 18px; color: #333; margin: 0 0 15px 0;">
        Hola <strong style="color: #667eea;">{{ $nombreUsuario }}</strong>,
    </p>
    
    <p style="font-size: 16px; color: #555; line-height: 1.6;">
        Te confirmamos que acabas de crear un grupo nuevo en el sistema de gestión de tareas.
    </p>
    
    <div style="background-color: white; padding: 20px; border-left: 4px solid #667eea; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
            📁 Nombre del Grupo:
        </p>
        <h2 style="margin: 10px 0 0 0; color: #667eea; font-size: 24px;">
            {{ $nombreGrupo }}
        </h2>
    </div>
</div>

{{-- Información adicional --}}
<div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0; color: #856404; font-size: 14px;">
        <strong>💡 Consejo:</strong> Ahora puedes comenzar a agregar listas y tareas a tu grupo.
    </p>
</div>

{{-- Botón de acción --}}
@component('mail::button', ['url' => env('APP_URL') . '/tasks/grupos/list', 'color' => 'primary'])
📋 Ver Mis Grupos
@endcomponent

{{-- Información de seguridad --}}
<div style="background-color: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0; color: #0c5460; font-size: 13px;">
        <strong>🔒 Seguridad:</strong> Si no realizaste esta acción, por favor contacta al administrador del sistema de inmediato.
    </p>
</div>

{{-- Firma --}}
<p style="margin-top: 30px; color: #666; font-size: 14px;">
    Saludos cordiales,<br>
    <strong>{{ config('app.name') }}</strong>
</p>

{{-- Footer --}}
<hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">

<p style="color: #999; font-size: 12px; text-align: center; line-height: 1.6;">
    Este es un correo automático generado por el sistema.<br>
    Por favor, no respondas a este mensaje.<br>
    <br>
    © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
</p>

@endcomponent