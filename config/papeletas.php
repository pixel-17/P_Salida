<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Horario laboral
    |--------------------------------------------------------------------------
    |
    | Usado por StorePapeletaRequest para no permitir crear papeletas con
    | hora_salida_programada (ni hora_retorno_programada, si se define) fuera
    | de este rango. Ajusta aquí, no en el FormRequest.
    |
    */
    'horario_laboral' => [
        'inicio' => '07:00',
        'fin' => '19:00',
    ],

    /*
    |--------------------------------------------------------------------------
    | Días no laborables
    |--------------------------------------------------------------------------
    |
    | Números de día de semana según Carbon: 0 = domingo, 1 = lunes, ...,
    | 6 = sábado. No se puede crear una papeleta con fecha_salida en uno de
    | estos días.
    |
    */
    'dias_no_laborables' => [0],

    /*
    |--------------------------------------------------------------------------
    | Horario límite de la garita
    |--------------------------------------------------------------------------
    |
    | Usado por MarcarSalidaVigilanteAction y MarcarRetornoVigilanteAction:
    | pasada esta hora el vigilante ya no puede confirmar salidas ni
    | retornos (sí puede seguir consultando "Hoy" y buscando). Ajusta aquí,
    | no en las Actions.
    |
    */
    'hora_limite_registro_garita' => '17:30',
];
