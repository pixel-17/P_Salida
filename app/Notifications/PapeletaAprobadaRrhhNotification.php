<?php

namespace App\Notifications;

class PapeletaAprobadaRrhhNotification extends BasePapeletaNotification
{
    public function tipo(): string
    {
        return 'PAPELETA_AUTORIZADA';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo} autorizada";
    }

    public function mensaje(): string
    {
        return "La papeleta {$this->papeleta->codigo} fue autorizada por RRHH. Ya puede marcar su salida.";
    }
}
