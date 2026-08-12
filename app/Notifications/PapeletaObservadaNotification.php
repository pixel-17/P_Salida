<?php

namespace App\Notifications;

class PapeletaObservadaNotification extends BasePapeletaNotification
{
    public function __construct(
        \App\Models\Papeleta $papeleta,
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
