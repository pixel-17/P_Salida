<?php

namespace App\Notifications;

class PapeletaCanceladaNotification extends BasePapeletaNotification
{
    public function tipo(): string
    {
        return 'PAPELETA_CANCELADA';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo} cancelada";
    }

    public function mensaje(): string
    {
        return 'Se canceló automáticamente: terminó el día y nunca se registró la marcación de salida.';
    }
}
