<?php

namespace App\Notifications;

class MarcacionRetornoNotification extends BasePapeletaNotification
{
    public function tipo(): string
    {
        return 'CONFIRMACION_PENDIENTE';
    }

    public function titulo(): string
    {
        return "{$this->papeleta->trabajador->name} marcó su retorno";
    }

    public function mensaje(): string
    {
        return "Papeleta {$this->papeleta->codigo}: pendiente de tu confirmación de retorno.";
    }
}
