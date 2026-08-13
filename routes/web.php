<?php

use App\Http\Controllers\AdjuntoController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AprobacionController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\MotivoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PapeletaController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\LiveCheckController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VigilanteController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('login');
});

require __DIR__.'/auth.php';
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {

    // ---------- Papeletas: accesible a los 3 roles, filtrado por Policy/scope ----------
    Route::get('/papeletas', [PapeletaController::class, 'index'])->name('papeletas.index');

    // ---------- Exportación a Excel/PDF: solo RRHH. Throttle porque cada
    // descarga arma el archivo completo en memoria (hasta MAX_FILAS_EXPORTAR
    // filas) — sin límite, un clic repetido o un script podría tumbar el
    // proceso PHP con exportaciones simultáneas. ----------
    Route::middleware(['role:RRHH', 'throttle:10,1'])->group(function () {
        Route::get('/papeletas/exportar', [PapeletaController::class, 'exportar'])->name('papeletas.exportar');
        Route::get('/papeletas/exportar/pdf', [PapeletaController::class, 'pdfReporte'])->name('papeletas.exportar-pdf');
    });

    Route::get('/papeletas/crear', [PapeletaController::class, 'create'])->name('papeletas.create');
    Route::post('/papeletas', [PapeletaController::class, 'store'])->name('papeletas.store');
    Route::get('/papeletas/{papeleta}', [PapeletaController::class, 'show'])->name('papeletas.show');
    Route::get('/papeletas/{papeleta}/pdf', [PapeletaController::class, 'pdfBoleta'])->name('papeletas.pdf');
    Route::get('/papeletas/{papeleta}/eventos', [PapeletaController::class, 'eventos'])->name('papeletas.eventos');

    // ---------- Flujo de aprobación: Jefe (SOLICITADO) y RRHH (APROBADO_JEFE) ----------
    // Throttle amplio (no son acciones que un jefe/RRHH haga en ráfaga
    // normalmente), pero evita que un doble-clic o un bot machaque el
    // endpoint de aprobar/rechazar.
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/papeletas/{papeleta}/aprobar', [AprobacionController::class, 'aprobar'])->name('papeletas.aprobar');
        Route::post('/papeletas/{papeleta}/rechazar', [AprobacionController::class, 'rechazar'])->name('papeletas.rechazar');
        Route::post('/papeletas/{papeleta}/observar', [AprobacionController::class, 'observar'])->name('papeletas.observar');
        Route::post('/papeletas/{papeleta}/responder-observacion', [AprobacionController::class, 'responderObservacion'])->name('papeletas.responder-observacion');
    });

    // ---------- Adjuntos ----------
    Route::post('/papeletas/{papeleta}/adjuntos', [AdjuntoController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('adjuntos.store');
    Route::get('/adjuntos/{adjunto}/descargar', [AdjuntoController::class, 'download'])->name('adjuntos.download');
    Route::delete('/adjuntos/{adjunto}', [AdjuntoController::class, 'destroy'])->name('adjuntos.destroy');

    // ---------- Notificaciones (campana) ----------
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('/notificaciones/{notificacion}/leida', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leida');
    Route::post('/notificaciones/leidas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.leidas');

    // ---------- Suscripción Web Push ----------
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('push.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');

    // ---------- Garita: solo VIGILANTE, y solo su propia sede (Policy/Controller) ----------
    // Límite generoso: en hora punta un vigilante puede escanear/buscar
    // decenas de papeletas seguidas, no queremos bloquear el flujo real de
    // la puerta; el límite solo frena un abuso claro (script, QR reader
    // en loop roto), no el uso humano normal.
    Route::middleware(['role:VIGILANTE', 'throttle:120,1'])->prefix('vigilancia')->name('vigilancia.')->group(function () {
        Route::get('/', [VigilanteController::class, 'index'])->name('index');
        Route::get('/buscar', [VigilanteController::class, 'buscar'])->name('buscar');
        Route::get('/resumen', [VigilanteController::class, 'resumen'])->name('resumen');
        Route::post('/papeletas/{papeleta}/salida', [VigilanteController::class, 'confirmarSalida'])->name('salida');
        Route::post('/papeletas/{papeleta}/retorno', [VigilanteController::class, 'confirmarRetorno'])->name('retorno');
    });

    // ---------- Panel y catálogos: solo ADMINISTRADOR ----------
    // Throttle de seguridad general sobre el panel administrativo completo
    // (lecturas y escrituras); 100/min es holgado para uso humano pero
    // corta cualquier automatización maliciosa contra los CRUDs.
    Route::middleware(['role:ADMINISTRADOR', 'throttle:100,1'])->group(function () {
        Route::get('/admin/live-check/{tabla}', LiveCheckController::class)->name('admin.live-check');
        Route::get('/admin/dashboard', AdminDashboardController::class)->name('admin.dashboard');
        Route::get('/admin/auditoria', [AuditLogController::class, 'index'])->name('admin.auditoria');

        Route::resource('areas', AreaController::class)->except(['show']);
        Route::resource('cargos', CargoController::class)->except(['show']);
        Route::resource('sedes', SedeController::class)->except(['show']);
        Route::resource('motivos', MotivoController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
    });
});
