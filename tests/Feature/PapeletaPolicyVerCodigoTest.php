<?php

use App\Models\Papeleta;
use Tests\Feature\Actions\PapeletaActionTestCase;

uses(PapeletaActionTestCase::class);

/**
 * El código/QR de la papeleta solo se muestra el día exactamente autorizado
 * (fecha_salida) — mismo criterio que ya aplicaba MarcarSalidaVigilanteAction
 * del lado del vigilante. Antes de este fix, PapeletaPolicy::verCodigo solo
 * miraba el estado y dejaba ver el código apenas RRHH aprobaba, sin importar
 * si la fecha autorizada todavía no llegaba.
 */
test('el trabajador NO puede ver el código si la fecha autorizada aún no llega', function () {
    $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
        'fecha_salida' => now()->addDays(3)->toDateString(),
    ]);

    expect($this->trabajador->can('verCodigo', $papeleta))->toBeFalse();
});

test('el trabajador SÍ puede ver el código el día exacto de la fecha autorizada', function () {
    $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
        'fecha_salida' => now()->toDateString(),
    ]);

    expect($this->trabajador->can('verCodigo', $papeleta))->toBeTrue();
});

test('el trabajador NO puede ver el código si la fecha autorizada ya pasó', function () {
    $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
        'fecha_salida' => now()->subDays(1)->toDateString(),
    ]);

    expect($this->trabajador->can('verCodigo', $papeleta))->toBeFalse();
});

test('el trabajador SÍ puede ver el código para el retorno (EN_CURSO) aunque haya cruzado de fecha', function () {
    $papeleta = $this->crearPapeleta('EN_CURSO', [
        // La salida fue ayer (p.ej. salió tarde en la noche); el retorno no
        // está atado a fecha_salida, igual que puede_retorno del vigilante.
        'fecha_salida' => now()->subDays(1)->toDateString(),
    ]);

    expect($this->trabajador->can('verCodigo', $papeleta))->toBeTrue();
});

test('otro usuario nunca puede ver el código de una papeleta ajena', function () {
    $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
        'fecha_salida' => now()->toDateString(),
    ]);

    expect($this->jefe->can('verCodigo', $papeleta))->toBeFalse();
});
