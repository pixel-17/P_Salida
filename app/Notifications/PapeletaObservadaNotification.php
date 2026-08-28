<?php

namespace App\Notifications;

use App\Models\Papeleta;

class PapeletaObservadaNotification extends BasePapeletaNotification
{
    public function __construct(
        Papeleta $papeleta,
        public string $comentario,
    ) {
        parent::__construct($papeleta);
    }

    public function tipo(): string
    {
        return 'PAPELETA_OBSERVADA';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo} observada";
    }

    public function mensaje(): string
    {
        return "Se requiere tu atención: {$this->comentario}";
    }
}
