<?php

/**
 * Diagnóstico v2: renderiza DE VERDAD la vista de papeletas/show (la
 * misma que usa tu navegador) usando datos reales de tu base de datos,
 * en el mismo orden exacto en que Laravel compila layout + componentes +
 * la vista. Si el bug del contador de @forelse/@empty aparece, lo vamos
 * a ver aquí también — y esta vez inspeccionamos los archivos compilados
 * en disco (storage/framework/views) para encontrar CUÁL vista original
 * quedó con la variable inválida.
 *
 * CÓMO USARLO:
 *   1. Copia este archivo a la raíz del proyecto:
 *      C:\laragon\www\P_Salida\diagnostico_forelse2.php
 *   2. En una terminal, parado en esa carpeta:
 *      php diagnostico_forelse2.php
 *      (opcional: puedes pasar el id de la papeleta que falla, ej:
 *       php diagnostico_forelse2.php 2)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1) Borramos el caché de vistas compiladas para empezar limpio.
$viewsCachePath = storage_path('framework/views');
foreach (glob($viewsCachePath.'/*.php') as $f) {
    @unlink($f);
}

// 2) Buscamos la papeleta a probar (por parámetro, o cualquiera si no se
//    especifica) y un usuario para autenticar (RRHH/ADMIN si existe).
$papeletaId = $argv[1] ?? null;

$papeleta = $papeletaId
    ? \App\Models\Papeleta::find($papeletaId)
    : \App\Models\Papeleta::first();

if (! $papeleta) {
    echo "No se encontró ninguna papeleta en la base de datos. Pasa un id existente como argumento.\n";
    exit(1);
}

$papeleta->load([
    'trabajador', 'jefe', 'area', 'sede', 'motivo', 'estado',
    'marcaciones', 'flujoAprobaciones.usuario', 'observaciones.usuario',
    'adjuntos', 'historial.usuario',
]);

$usuario = \App\Models\User::where('id', $papeleta->trabajador_id)->first()
    ?? \App\Models\User::first();

if (! $usuario) {
    echo "No se encontró ningún usuario en la base de datos para autenticar.\n";
    exit(1);
}

auth()->login($usuario);

echo "Probando renderizar papeletas.show con la papeleta #{$papeleta->id} ({$papeleta->codigo}) como usuario '{$usuario->name}'...\n\n";

try {
    $html = view('papeletas.show', ['papeleta' => $papeleta])->render();
    echo "RENDER OK — no se reprodujo el error con esta papeleta/usuario.\n";
    echo 'Largo del HTML generado: '.strlen($html)." caracteres.\n";
} catch (\Throwable $e) {
    echo '[EXCEPCION AL RENDERIZAR] '.get_class($e).': '.$e->getMessage()."\n";
    echo 'Archivo: '.$e->getFile().':'.$e->getLine()."\n\n";
}

// 3) Revisamos TODOS los archivos que quedaron compilados en disco tras
//    este render (sin importar si hubo excepción o no) y buscamos la
//    variable inválida, y de paso mostramos a qué vista original
//    corresponde cada uno (Laravel deja un comentario /**PATH ... **/
//    al final de cada archivo compilado).
echo "\n----------------------------------------\n";
echo "Revisando archivos compilados en storage/framework/views...\n\n";

$encontrado = false;

foreach (glob($viewsCachePath.'/*.php') as $compiledFile) {
    $contenido = file_get_contents($compiledFile);

    if (preg_match_all('/\$__empty_(-?\d+)/', $contenido, $m)) {
        foreach (array_unique($m[1]) as $n) {
            if ((int) $n <= 0) {
                $origen = 'desconocido';
                if (preg_match('/\/\*\*PATH (.+) ENDPATH\*\*\//', $contenido, $pathMatch)) {
                    $origen = $pathMatch[1];
                }
                echo "[VARIABLE INVALIDA] \$__empty_{$n} en el compilado de:\n  {$origen}\n  (caché: {$compiledFile})\n\n";
                $encontrado = true;
            }
        }
    }
}

if (! $encontrado) {
    echo "No se encontró ninguna variable \$__empty_ inválida en los archivos compilados.\n";
    echo "Si el error SÍ ocurrió arriba, puede que se dispare solo con otra\n";
    echo "papeleta/estado en particular. Prueba pasando distintos ids:\n";
    echo "  php diagnostico_forelse2.php 1\n";
    echo "  php diagnostico_forelse2.php 3\n";
    echo "  etc.\n";
} else {
    echo "----------------------------------------\n";
    echo "Ese es el archivo real que hay que revisar/arreglar.\n";
}
