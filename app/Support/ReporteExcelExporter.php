<?php

namespace App\Support;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Escritura y estilos del Excel de reportes (ReporteController::exportar()).
 * Separado del controlador para que el detalle de PhpSpreadsheet (colores,
 * bordes, autofiltro, panel congelado) no se mezcle con la orquestación
 * HTTP — mismo criterio que ReporteSalidasCalculator para los cálculos.
 */
class ReporteExcelExporter
{
    /**
     * Escribe una hoja de ranking dentro del spreadsheet: fila de título con
     * el rango de fechas (si se pasa $subtitulo), encabezado con color y
     * panel congelado, filas de datos con franjas alternadas y bordes
     * suaves, y autofiltro (flechas desplegables tipo Excel en cada
     * columna, incluida "Trabajador"/nombre) sobre todo el rango — así se
     * puede filtrar u ordenar por cualquier columna sin tocar código.
     * $filaCallback convierte cada elemento de $datos en el arreglo de
     * columnas a escribir, en el mismo orden que $encabezados.
     */
    public static function hojaRanking(
        Spreadsheet $spreadsheet,
        string $titulo,
        array $encabezados,
        Collection $datos,
        \Closure $filaCallback,
        bool $primeraHoja = false,
        ?string $subtitulo = null,
    ): void {
        $hoja = $primeraHoja ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
        $hoja->setTitle($titulo);

        $ultimaColumna = Coordinate::stringFromColumnIndex(count($encabezados));

        // Fila de título con el rango de fechas del reporte, para que el
        // archivo tenga sentido por sí solo aunque se comparta suelto (sin
        // el nombre de archivo, que sí trae la fecha, a la vista).
        $filaEncabezado = 1;
        if ($subtitulo) {
            $hoja->setCellValue('A1', "{$titulo} — {$subtitulo}");
            $hoja->mergeCells("A1:{$ultimaColumna}1");
            $hoja->getRowDimension(1)->setRowHeight(24);
            $hoja->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '111827']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            ]);
            $filaEncabezado = 2;
        }

        $hoja->fromArray($encabezados, null, "A{$filaEncabezado}");
        $rangoEncabezado = "A{$filaEncabezado}:{$ultimaColumna}{$filaEncabezado}";
        $hoja->getRowDimension($filaEncabezado)->setRowHeight(20);
        $hoja->getStyle($rangoEncabezado)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $filaInicioDatos = $filaEncabezado + 1;
        $fila = $filaInicioDatos;
        foreach ($datos as $item) {
            $hoja->fromArray(self::sanitizarFila($filaCallback($item)), null, "A{$fila}");
            $fila++;
        }
        $filaFinDatos = $fila - 1;

        if ($filaFinDatos >= $filaInicioDatos) {
            $rangoDatos = "A{$filaInicioDatos}:{$ultimaColumna}{$filaFinDatos}";
            $hoja->getStyle($rangoDatos)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);

            // Franjas alternadas (zebra) para que la fila se pueda seguir
            // con la vista en tablas largas.
            for ($f = $filaInicioDatos; $f <= $filaFinDatos; $f++) {
                if (($f - $filaInicioDatos) % 2 === 1) {
                    $hoja->getStyle("A{$f}:{$ultimaColumna}{$f}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
                }
            }
        }

        // Autofiltro: agrega las flechas desplegables de Excel en cada
        // encabezado (Trabajador, Área, Jefe, etc.) para filtrar/ordenar
        // sin escribir fórmulas ni tocar la tabla dinámica de origen.
        $hoja->setAutoFilter("A{$filaEncabezado}:{$ultimaColumna}".max($filaFinDatos, $filaEncabezado));

        // Encabezado siempre visible al bajar por filas largas.
        $hoja->freezePane("A{$filaInicioDatos}");

        foreach (range('A', $ultimaColumna) as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }
    }

    /**
     * Defensa contra "fórmula injection" (CSV/Excel Injection): si una
     * celda de texto empieza con =, +, -, @ o un tab/retorno de carro,
     * Excel/Sheets la interpreta como fórmula al abrir el archivo, no
     * como texto literal. Los valores que llegan aquí (nombre de
     * trabajador, área, motivo, destino) los ingresa el propio staff al
     * crear usuarios/catálogos — ver UserController/EquipoController —,
     * así que el vector real es "un compañero con nombre raro", no un
     * atacante externo anónimo; aun así, es una defensa barata que
     * conviene tener antes de que el archivo se comparta fuera del
     * sistema. Se antepone un apóstrofo para forzar texto plano, igual
     * que Excel hace internamente cuando uno mismo pega ese valor a mano
     * en una celda con formato texto.
     */
    private static function sanitizarFila(array $fila): array
    {
        return array_map(function ($valor) {
            if (is_string($valor) && $valor !== '' && in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'".$valor;
            }

            return $valor;
        }, $fila);
    }
}
