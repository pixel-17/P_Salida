<div style="font-family: Arial, sans-serif; background:#f4f6f8; padding:20px;">
    
    <div style="max-width:600px; margin:auto; background:white; border-radius:8px; padding:24px;">
        
        <h2 style="color:#111827;">Recuperar Contraseña</h2>

        <p>Hola <strong>{{ $user->name }}</strong>,</p>

        <p>
            Recibimos una solicitud para restablecer tu contraseña.
            Haz clic en el botón de abajo:
        </p>

        <div style="text-align:center; margin:30px 0;">
            <a href="{{ $enlace }}" 
               style="background:#2563eb; color:white; padding:12px 20px; 
                      text-decoration:none; border-radius:6px; display:inline-block;">
                Restablecer Contraseña
            </a>
        </div>

        <p style="color:#6b7280;">
            Este enlace expirará en <strong>30 minutos</strong>.
        </p>

        <p style="color:#6b7280;">
            Si no solicitaste este cambio, puedes ignorar este correo.
        </p>

        <hr style="margin:20px 0;">

        <p style="font-size:12px; color:#9ca3af;">
            Sistema S-Topic
        </p>
    </div>

</div>