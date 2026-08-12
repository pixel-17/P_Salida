<?php

namespace App\Notifications;

class PapeletaRechazadaNotification extends BasePapeletaNotification
{
    public function __construct(
        \App\Models\Papeleta $papeleta,
        public string $comentario,
    ) {
        parent::__construct($papeleta);
    }

    public function tipo(): string
    {
        return 'PAPELETA_RECHAZADA';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo} rechazada";
    }

    public function mensaje(): string
    {
        return "Motivo: {$this->comentario}";
    }
}
