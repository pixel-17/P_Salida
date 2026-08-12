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
     * Cierre automático de fin de día: papeleta aprobada por RRHH que nunca
     * llegó a marcar salida (el trabajador nunca se presentó). Ver
     * App\Console\Commands\CancelarPapeletasNoPresentadasCommand.
     */
    case CANCELADO = 'CANCELADO';
}
