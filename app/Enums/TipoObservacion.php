<?php

namespace App\Enums;

enum TipoObservacion: string
{
    case ADMINISTRATIVA = 'ADMINISTRATIVA';
    case JUSTIFICACION = 'JUSTIFICACION';

    public function label(): string
    {
        return match ($this) {
            self::ADMINISTRATIVA => 'Observación administrativa',
            self::JUSTIFICACION => 'Falta justificación',
        };
    }
}
