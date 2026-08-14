<?php

namespace App\Services;

use App\Models\Atencion;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class NotificacionEstudianteService
{
    /**
     * Enviar notificación al alumno cuando finaliza atención
     */
    public static function notificarAtencionCompletada(Atencion $atencion)
    {
        try {
            // Cargar relaciones
            $atencion->load(['estudiante', 'motivo.categoria']);

            // Verificar que el estudiante tiene email
            if (!$atencion->estudiante->email) {
                \Log::warning('Estudiante sin email: ' . $atencion->estudiante->id);
                return false;
            }

            $email = $atencion->estudiante->email;

            // Enviar email
            Mail::send('emails.atencion-completada', 
                ['estudiante' => $atencion->estudiante, 'atencion' => $atencion], 
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Tu Atención en S-Topic fue Exitosa');
                }
            );

            // Registrar notificación enviada
            Notificacion::create([
                'estudiante_id' => $atencion->estudiante->id,
                'atencion_id' => $atencion->id,
                'email' => $email,
                'enviado' => true,
                'enviado_en' => Carbon::now(),
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Error enviando notificación: ' . $e->getMessage());
            return false;
        }
    }
}