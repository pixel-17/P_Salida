<?php

namespace App\Notifications;

use App\Models\Papeleta;

class ObservacionRespondidaNotification extends BasePapeletaNotification
{
    public function __construct(
        Papeleta $papeleta,
        public string $respuesta,
    ) {
        parent::__construct($papeleta);
    }

    public function tipo(): string
    {
        return 'OBSERVACION_RESPONDIDA';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo}: observación respondida";
    }

    public function mensaje(): string
    {
        return "El trabajador respondió: {$this->respuesta}";
    }
}
