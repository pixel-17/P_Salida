<?php

namespace App\Enums;

enum RolUsuario: string
{
    case TRABAJADOR = 'TRABAJADOR';
    case JEFE = 'JEFE';
    case RRHH = 'RRHH';
    case ADMINISTRADOR = 'ADMINISTRADOR';
    case VIGILANTE = 'VIGILANTE';

    public function label(): string
    {
        return match ($this) {
            self::TRABAJADOR => 'Trabajador',
            self::JEFE => 'Jefe de Área',
            self::RRHH => 'Recursos Humanos',
            self::ADMINISTRADOR => 'Administrador',
            self::VIGILANTE => 'Vigilante',
        };
    }
}
