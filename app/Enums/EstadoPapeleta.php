<?php

namespace App\Enums;

/**
 * No es una columna enum de BD: espeja los códigos de la tabla catálogo
 * `estados` para evitar strings mágicos en Actions/Policies/Controllers.
 */
enum EstadoPapeleta: string
{
    case SOLICITADO = 'SOLICITADO';
    case APROBADO_JEFE = 'APROBADO_JEFE';
    case APROBADO_RRHH = 'APROBADO_RRHH';
    case EN_CURSO = 'EN_CURSO';
    case FINALIZADO = 'FINALIZADO';
    case RECHAZADO = 'RECHAZADO';
    case OBSERVADO = 'OBSERVADO';
    case VENCIDA = 'VENCIDA';

    /**
     * Cancelación EXCLUSIVAMENTE manual: el propio trabajador cancela su
     * papeleta desde su usuario, antes de salir (ver
     * App\Policies\PapeletaPolicy::cancelar y App\Actions\CancelarPapeletaAction).
     * Ya no se usa para nada automático — lo que antes caía acá (papeleta
     * que se quedó en trámite y nunca se presentó) ahora cae en VENCIDA,
     * ver App\Console\Commands\CancelarPapeletasNoPresentadasCommand.
     */
    case CANCELADO = 'CANCELADO';
}
