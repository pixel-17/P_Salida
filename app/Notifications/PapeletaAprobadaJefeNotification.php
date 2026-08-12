<?php

namespace App\Notifications;

class PapeletaAprobadaJefeNotification extends BasePapeletaNotification
{
    public function tipo(): string
    {
        return 'PAPELETA_AUTORIZADA';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo} aprobada por el jefe";
    }

    public function mensaje(): string
    {
        return "La papeleta {$this->papeleta->codigo} fue aprobada por el jefe de área y está pendiente de RRHH.";
    }
}
