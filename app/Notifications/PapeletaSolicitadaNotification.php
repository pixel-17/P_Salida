<?php

namespace App\Notifications;

class PapeletaSolicitadaNotification extends BasePapeletaNotification
{
    public function tipo(): string
    {
        return 'PAPELETA_PENDIENTE';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo} pendiente de aprobación";
    }

    public function mensaje(): string
    {
        $nombre = $this->papeleta->trabajador->name;

        return "{$nombre} solicitó una papeleta de salida para el {$this->papeleta->fecha_salida->format('d/m/Y')}.";
    }
}
