<?php

namespace App\Enums;

enum AccionFlujo: string
{
    case APROBADO = 'APROBADO';
    case RECHAZADO = 'RECHAZADO';
    case OBSERVADO = 'OBSERVADO';
}
