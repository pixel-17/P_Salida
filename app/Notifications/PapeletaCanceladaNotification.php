<?php

namespace App\Notifications;

use App\Models\Papeleta;

/**
 * CANCELADA ahora es exclusivamente la cancelación manual que hace el
 * propio trabajador desde su usuario (ver PapeletaController::cancelar).
 * El motivo que escribió el trabajador es obligatorio y va en el mensaje.
 */
class PapeletaCanceladaNotification extends BasePapeletaNotification
{
    public function __construct(
        Papeleta $papeleta,
        public string $motivo,
    ) {
        parent::__construct($papeleta);
    }

    public function tipo(): string
    {
        return 'PAPELETA_CANCELADA';
    }

    public function titulo(): string
    {
        return "Papeleta {$this->papeleta->codigo} cancelada";
    }

    public function mensaje(): string
    {
        return "El trabajador la canceló. Motivo: {$this->motivo}";
    }
}
