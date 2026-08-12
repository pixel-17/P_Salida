<?php

namespace App\Notifications;

class MarcacionSalidaNotification extends BasePapeletaNotification
{
    public function tipo(): string
    {
        return 'RETORNO_PENDIENTE';
    }

    public function titulo(): string
    {
        return "{$this->papeleta->trabajador->name} marcó su salida";
    }

    public function mensaje(): string
    {
        return "Papeleta {$this->papeleta->codigo}: salida registrada con GPS.";
    }
}
