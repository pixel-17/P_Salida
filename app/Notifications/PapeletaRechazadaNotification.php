<?php

namespace App\Notifications;

use App\Models\Papeleta;

class PapeletaRechazadaNotification extends BasePapeletaNotification
{
    public function __construct(
        Papeleta $papeleta,
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
