<?php

namespace Tests\Unit;

use App\Support\ReporteExcelExporter;
use PHPUnit\Framework\TestCase;

/**
 * ReporteExcelExporter::sanitizarFila evita "fórmula injection" (CSV/Excel
 * Injection): si un nombre de trabajador/área/motivo empieza con =, +, -,
 *
 * @ o un tab/retorno de carro, Excel lo interpreta como fórmula al abrir
 * el archivo en vez de como texto literal. Se prueba el método directo
 * (es privado, se invoca vía Reflection) para no depender de BD ni de
 * generar un .xlsx completo solo para esto.
 */
class ReporteExcelExporterTest extends TestCase
{
    private function sanitizarFila(array $fila): array
    {
        $metodo = new \ReflectionMethod(ReporteExcelExporter::class, 'sanitizarFila');
        $metodo->setAccessible(true);

        return $metodo->invoke(null, $fila);
    }

    public function test_antepone_apostrofo_a_celdas_que_empiezan_con_caracteres_de_formula(): void
    {
        $fila = ['=SUM(A1:A9)', '+1234', '-1234', '@ejemplo', "\tHola", "\rHola"];

        $sanitizada = $this->sanitizarFila($fila);

        $this->assertSame([
            "'=SUM(A1:A9)",
            "'+1234",
            "'-1234",
            "'@ejemplo",
            "'\tHola",
            "'\rHola",
        ], $sanitizada);
    }

    public function test_no_toca_texto_normal_ni_valores_no_string(): void
    {
        $fila = ['Juan Pérez', 'Área de Sistemas', 12, 3.5, null];

        $sanitizada = $this->sanitizarFila($fila);

        $this->assertSame(['Juan Pérez', 'Área de Sistemas', 12, 3.5, null], $sanitizada);
    }

    public function test_no_toca_string_vacio(): void
    {
        $this->assertSame([''], $this->sanitizarFila(['']));
    }
}
