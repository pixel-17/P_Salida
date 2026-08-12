<?php

namespace App\Enums;

enum CanalNotificacion: string
{
    case SISTEMA = 'SISTEMA';
    case PUSH = 'PUSH';
    case CORREO = 'CORREO';
}
