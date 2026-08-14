<div style="font-family: Arial, sans-serif; background:#f4f6f8; padding:20px;">

    <div style="max-width:600px; margin:auto; background:#ffffff; border-radius:10px; padding:24px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

        <h2 style="color:#111827;">🩺 Atención Registrada</h2>

        <p>Hola <strong>{{ $estudiante->nombres }}</strong>,</p>

        <p>Tu atención médica ha sido registrada exitosamente en el sistema <strong>S-Topic</strong>.</p>

        <hr style="margin:20px 0;">

        <h3 style="color:#1f2937;">📋 Detalles de la atención</h3>

        <p><strong>Fecha:</strong> {{ $atencion->fecha->format('d/m/Y H:i') }}</p>
        <p><strong>Motivo:</strong> {{ $atencion->motivo->nombre }}</p>
        <p><strong>Categoría:</strong> {{ $atencion->motivo->categoria->nombre }}</p>

        @if($atencion->observaciones)
            <div style="margin-top:16px;">
                <strong>Observaciones:</strong>
                <p style="margin:6px 0; color:#374151;">
                    {{ $atencion->observaciones }}
                </p>
            </div>
        @endif

        <hr style="margin:20px 0;">

        <p style="color:#6b7280;">
            Si tienes dudas, puedes acercarte al área de tópico del ISTTA.
        </p>

        <p style="font-size:12px; color:#9ca3af;">
            Sistema S-Topic
        </p>

    </div>

</div>