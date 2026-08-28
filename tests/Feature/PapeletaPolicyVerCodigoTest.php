<?php

namespace Tests\Feature;

use App\Models\Papeleta;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\PapeletaActionTestCase;

/**
 * El código/QR de la papeleta solo se muestra el día exactamente autorizado
 * (fecha_salida) — mismo criterio que ya aplicaba MarcarSalidaVigilanteAction
 * del lado del vigilante. Antes de este fix, PapeletaPolicy::verCodigo solo
 * miraba el estado y dejaba ver el código apenas RRHH aprobaba, sin importar
 * si la fecha autorizada todavía no llegaba.
 */
class PapeletaPolicyVerCodigoTest extends PapeletaActionTestCase
{
    #[Test]
    #[TestDox('el trabajador NO puede ver el código si la fecha autorizada aún no llega')]
    public function trabajador_no_ve_codigo_antes_de_fecha_autorizada(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertFalse($this->trabajador->can('verCodigo', $papeleta));
    }

    #[Test]
    #[TestDox('el trabajador SÍ puede ver el código el día exacto de la fecha autorizada')]
    public function trabajador_ve_codigo_el_dia_exacto(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->toDateString(),
        ]);

        $this->assertTrue($this->trabajador->can('verCodigo', $papeleta));
    }

    #[Test]
    #[TestDox('el trabajador NO puede ver el código si la fecha autorizada ya pasó')]
    public function trabajador_no_ve_codigo_despues_de_fecha_autorizada(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->subDays(1)->toDateString(),
        ]);

        $this->assertFalse($this->trabajador->can('verCodigo', $papeleta));
    }

    #[Test]
    #[TestDox('el trabajador SÍ puede ver el código para el retorno (EN_CURSO) aunque haya cruzado de fecha')]
    public function trabajador_ve_codigo_en_curso_aunque_cruce_fecha(): void
    {
        $papeleta = $this->crearPapeleta('EN_CURSO', [
            // La salida fue ayer (p.ej. salió tarde en la noche); el retorno
            // no está atado a fecha_salida, igual que puede_retorno del
            // vigilante.
            'fecha_salida' => now()->subDays(1)->toDateString(),
        ]);

        $this->assertTrue($this->trabajador->can('verCodigo', $papeleta));
    }

    #[Test]
    #[TestDox('otro usuario nunca puede ver el código de una papeleta ajena')]
    public function otro_usuario_no_ve_codigo_de_papeleta_ajena(): void
    {
        $papeleta = $this->crearPapeleta('APROBADO_RRHH', [
            'fecha_salida' => now()->toDateString(),
        ]);

        $this->assertFalse($this->jefe->can('verCodigo', $papeleta));
    }
}
