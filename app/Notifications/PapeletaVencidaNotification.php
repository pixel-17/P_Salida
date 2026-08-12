<?php

namespace App\Notifications;

class PapeletaVencidaNotification extends BasePapeletaNotification
{
    public function tipo(): string
    {
        return 'PAPELETA_VENCIDA';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo} vencida";
    }

    public function mensaje(): string
    {
        return "No se registró el retorno dentro del horario permitido. Requiere regularización.";
    }
}
