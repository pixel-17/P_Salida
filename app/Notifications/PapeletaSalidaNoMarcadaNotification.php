<?php

namespace App\Notifications;

class PapeletaSalidaNoMarcadaNotification extends BasePapeletaNotification
{
    public function tipo(): string
    {
        return 'PAPELETA_SALIDA_NO_MARCADA';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo}: salida no marcada";
    }

    public function mensaje(): string
    {
        return 'Ya pasó la hora de salida programada y el vigilante todavía no registra la marcación. Si no se presenta, la papeleta se cancelará automáticamente al final del día.';
    }
}
