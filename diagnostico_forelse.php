<?php

/**
 * Diagnóstico: encuentra qué archivo .blade.php está corrompiendo el
 * contador interno de Blade para @forelse/@empty (el que causa el error
 * "syntax error, unexpected token '='" en $__empty_-1, $__empty_0, etc.
 * cuando Laravel intenta compilar OTRA vista después, como su propia
 * página de error).
 *
 * CÓMO USARLO:
 *   1. Copia este archivo a la raíz de tu proyecto:
 *      C:\laragon\www\P_Salida\diagnostico_forelse.php
 *   2. Corre en una terminal, parado en esa carpeta:
 *      php diagnostico_forelse.php
 *   3. Te va a imprimir, para cada vista, si compila bien o si deja el
 *      contador de @forelse desbalanceado. Al final te dice el/los
 *      archivo(s) culpables.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/** @var \Illuminate\View\Compilers\BladeCompiler $compiler */
$compiler = $app->make('blade.compiler');

$reflection = new ReflectionClass($compiler);
$prop = $reflection->getProperty('forElseCounter');
$prop->setAccessible(true);

$viewsPath = __DIR__.'/resources/views';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsPath, FilesystemIterator::SKIP_DOTS)
);

$culpables = [];
$totalArchivos = 0;

foreach ($iterator as $file) {
    if (! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $totalArchivos++;
    $relativo = str_replace($viewsPath.DIRECTORY_SEPARATOR, '', $file->getPathname());

    // Reiniciamos el contador ANTES de compilar este archivo, para
    // aislar el efecto de CADA archivo por separado.
    $prop->setValue($compiler, 0);

    $contenido = file_get_contents($file->getPathname());

    try {
        $compiled = $compiler->compileString($contenido);
    } catch (\Throwable $e) {
        echo "[ERROR AL COMPILAR] {$relativo} -> {$e->getMessage()}\n";
        $culpables[] = $relativo.' (excepción al compilar: '.$e->getMessage().')';

        continue;
    }

    $counterFinal = $prop->getValue($compiler);

    // Si el contador no vuelve a 0, este archivo por sí solo deja el
    // compilador "sucio" para el siguiente archivo que se compile.
    if ($counterFinal !== 0) {
        echo "[DESBALANCEADO] {$relativo} -> el contador queda en {$counterFinal} (debería volver a 0)\n";
        $culpables[] = $relativo." (contador termina en {$counterFinal})";
    }

    // Además, revisamos directamente el PHP compilado por si aparece
    // una variable de índice inválido (negativo o cero), que es
    // exactamente el patrón que rompe con "unexpected token '='".
    if (preg_match_all('/\$__empty_(-?\d+)/', $compiled, $m)) {
        foreach (array_unique($m[1]) as $n) {
            if ((int) $n <= 0) {
                echo "[VARIABLE INVALIDA] {$relativo} -> genera \$__empty_{$n}\n";
                $culpables[] = $relativo." (genera \$__empty_{$n})";
            }
        }
    }
}

echo "\n----------------------------------------\n";
echo "Archivos .blade.php revisados: {$totalArchivos}\n";

if (empty($culpables)) {
    echo "No se encontró ningún archivo individualmente desbalanceado.\n";
    echo "Esto sugiere que el problema aparece por la COMBINACIÓN de dos\n";
    echo "archivos compilados en la misma petición (p. ej. un layout +\n";
    echo "un componente), no por uno solo. Copia y pégame esta salida\n";
    echo "completa junto con el contenido de storage/logs/laravel.log\n";
    echo "y seguimos desde ahí.\n";
} else {
    echo "Archivo(s) que corrompen el contador de @forelse/@empty:\n";
    foreach (array_unique($culpables) as $c) {
        echo " - {$c}\n";
    }
    echo "\nRevisa que en ese archivo cada '@empty' (sin paréntesis) esté\n";
    echo "SIEMPRE precedido por su propio '@forelse(...)' y seguido de\n";
    echo "'@endforelse' — no puede quedar un '@empty' suelto ni un\n";
    echo "'@forelse' sin cerrar.\n";
}
