<?php

namespace Database\Seeders;

use App\Enums\AccionFlujo;
use App\Enums\CanalNotificacion;
use App\Enums\EstadoPapeleta;
use App\Enums\TipoMarcacion;
use App\Enums\TipoObservacion;
use App\Models\Adjunto;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Estado;
use App\Models\FlujoAprobacion;
use App\Models\HistorialPapeleta;
use App\Models\Marcacion;
use App\Models\Motivo;
use App\Models\NotificacionSistema;
use App\Models\Observacion;
use App\Models\Papeleta;
use App\Models\PushSubscription;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos de prueba: mínimo 5 filas reales en cada tabla del dominio, para
 * poder probar listados, filtros, paginación y estados sin tener que crear
 * todo a mano. Requiere que RoleSeeder y EstadoSeeder hayan corrido antes
 * (DatabaseSeeder ya respeta ese orden). Es idempotente: se puede correr
 * varias veces sin duplicar filas (usa firstOrCreate/updateOrCreate donde
 * aplica, y trunca las tablas transaccionales al inicio).
 */
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Catálogos base: 5 de cada uno ----------

        $areas = collect([
            ['nombre' => 'Gerencia de Administración', 'siglas' => 'GA'],
            ['nombre' => 'Gerencia de Operaciones', 'siglas' => 'GO'],
            ['nombre' => 'Recursos Humanos', 'siglas' => 'RRHH'],
            ['nombre' => 'Tecnología de la Información', 'siglas' => 'TI'],
            ['nombre' => 'Logística', 'siglas' => 'LOG'],
        ])->map(fn ($a) => Area::firstOrCreate(['nombre' => $a['nombre']], $a));

        $cargos = collect([
            ['area' => 0, 'nombre' => 'Asistente Administrativo'],
            ['area' => 1, 'nombre' => 'Supervisor de Operaciones'],
            ['area' => 2, 'nombre' => 'Analista de RRHH'],
            ['area' => 3, 'nombre' => 'Desarrollador de Software'],
            ['area' => 4, 'nombre' => 'Coordinador de Almacén'],
        ])->map(fn ($c) => Cargo::firstOrCreate(
            ['area_id' => $areas[$c['area']]->id, 'nombre' => $c['nombre']],
        ));

        $sedes = collect([
            ['nombre' => 'Sede Central', 'direccion' => 'Plaza de Armas s/n', 'latitud' => -12.0463731, 'longitud' => -77.0427934],
            ['nombre' => 'Sede Norte', 'direccion' => 'Av. Túpac Amaru 1500', 'latitud' => -11.9868, 'longitud' => -77.0568],
            ['nombre' => 'Sede Sur', 'direccion' => 'Av. Pachacútec 800', 'latitud' => -12.1850, 'longitud' => -76.9950],
            ['nombre' => 'Sede Este', 'direccion' => 'Av. Nicolás Ayllón 3200', 'latitud' => -12.0432, 'longitud' => -76.9560],
            ['nombre' => 'Sede Callao', 'direccion' => 'Av. Sáenz Peña 250', 'latitud' => -12.0565, 'longitud' => -77.1181],
        ])->map(fn ($s) => Sede::firstOrCreate(['nombre' => $s['nombre']], $s));

        $motivos = collect([
            ['nombre' => 'Trámite documentario', 'requiere_documento' => false, 'goce_haber' => true, 'max_horas' => 4],
            ['nombre' => 'Cita médica', 'requiere_documento' => true, 'goce_haber' => true, 'max_horas' => 6],
            ['nombre' => 'Trámite bancario', 'requiere_documento' => false, 'goce_haber' => true, 'max_horas' => 3],
            ['nombre' => 'Capacitación externa', 'requiere_documento' => true, 'goce_haber' => true, 'max_horas' => 8],
            ['nombre' => 'Asunto personal', 'requiere_documento' => false, 'goce_haber' => false, 'max_horas' => null],
        ])->map(fn ($m) => Motivo::firstOrCreate(['nombre' => $m['nombre']], $m));

        // ---------- Usuarios: roles fijos + 5 trabajadores ----------

        $admin = User::updateOrCreate(['email' => 'admin@demo.test'], [
            'name' => 'Ana Administradora', 'password' => Hash::make('password'),
            'dni' => '10000001', 'cargo_id' => $cargos[0]->id, 'sede_id' => $sedes[0]->id,
            'email_verified_at' => now(),
        ]);
        $admin->syncRoles('ADMINISTRADOR');

        $jefe = User::updateOrCreate(['email' => 'jefe@demo.test'], [
            'name' => 'Jorge Jefe de Área', 'password' => Hash::make('password'),
            'dni' => '10000002', 'cargo_id' => $cargos[1]->id, 'sede_id' => $sedes[0]->id,
            'email_verified_at' => now(),
        ]);
        $jefe->syncRoles('JEFE');

        $rrhh = User::updateOrCreate(['email' => 'rrhh@demo.test'], [
            'name' => 'Rosa RRHH', 'password' => Hash::make('password'),
            'dni' => '10000003', 'cargo_id' => $cargos[2]->id, 'sede_id' => $sedes[0]->id,
            'email_verified_at' => now(),
        ]);
        $rrhh->syncRoles('RRHH');

        $vigilantes = collect(range(1, 5))->map(function (int $i) use ($sedes, $cargos) {
            $v = User::updateOrCreate(['email' => "vigilante{$i}@demo.test"], [
                'name' => "Victor Vigilante {$i}", 'password' => Hash::make('password'),
                'dni' => '1000001'.$i, 'cargo_id' => $cargos[4]->id,
                'sede_id' => $sedes[($i - 1) % $sedes->count()]->id,
                'email_verified_at' => now(),
            ]);
            $v->syncRoles('VIGILANTE');

            return $v;
        });

        $nombres = ['Tito Trabajador', 'Fernanda Flores', 'Mario Medina', 'Carla Castro', 'Diego Delgado'];
        $trabajadores = collect($nombres)->map(function (string $nombre, int $i) use ($cargos, $sedes, $jefe) {
            $t = User::updateOrCreate(['email' => 'trabajador'.($i + 1).'@demo.test'], [
                'name' => $nombre, 'password' => Hash::make('password'),
                'dni' => '1000002'.$i, 'cargo_id' => $cargos[$i % $cargos->count()]->id,
                'sede_id' => $sedes[$i % $sedes->count()]->id, 'jefe_id' => $jefe->id,
                'email_verified_at' => now(),
            ]);
            $t->syncRoles('TRABAJADOR');

            return $t;
        });

        // ---------- Push subscriptions: 5 (una por trabajador) ----------

        $trabajadores->each(function (User $t, int $i) {
            PushSubscription::updateOrCreate(
                ['endpoint_hash' => hash('sha256', "fake-endpoint-{$i}")],
                [
                    'user_id' => $t->id,
                    'endpoint' => "https://fcm.googleapis.com/fcm/send/fake-{$i}",
                    'p256dh' => Str::random(88),
                    'auth_token' => Str::random(24),
                    'user_agent' => 'Mozilla/5.0 (prueba semilla)',
                    'activo' => true,
                ]
            );
        });

        // ---------- Papeletas + todo lo que cuelga de ellas ----------
        // Se truncan las tablas transaccionales para que el seeder sea
        // repetible sin ir acumulando filas cada vez que se corre.
        Adjunto::query()->delete();
        Observacion::query()->delete();
        NotificacionSistema::query()->delete();
        HistorialPapeleta::query()->delete();
        FlujoAprobacion::query()->delete();
        Marcacion::query()->delete();
        Papeleta::query()->forceDelete();

        $estadoSolicitado = Estado::porCodigo(EstadoPapeleta::SOLICITADO);
        $estadoAprobadoJefe = Estado::porCodigo(EstadoPapeleta::APROBADO_JEFE);
        $estadoEnCurso = Estado::porCodigo(EstadoPapeleta::EN_CURSO);
        $estadoFinalizado = Estado::porCodigo(EstadoPapeleta::FINALIZADO);
        $estadoObservado = Estado::porCodigo(EstadoPapeleta::OBSERVADO);

        // Un escenario distinto por papeleta, para poder probar cada estado
        // del flujo: solicitada, aprobada por jefe, en curso, finalizada,
        // observada.
        $escenarios = ['SOLICITADO', 'APROBADO_JEFE', 'EN_CURSO', 'FINALIZADO', 'OBSERVADO'];

        $trabajadores->each(function (User $trabajador, int $i) use (
            $motivos, $vigilantes, $jefe, $rrhh,
            $estadoSolicitado, $estadoAprobadoJefe, $estadoEnCurso, $estadoFinalizado, $estadoObservado,
            $escenarios,
        ) {
            $escenario = $escenarios[$i];
            $estadoInicial = match ($escenario) {
                'SOLICITADO' => $estadoSolicitado,
                'APROBADO_JEFE' => $estadoAprobadoJefe,
                'EN_CURSO' => $estadoEnCurso,
                'FINALIZADO' => $estadoFinalizado,
                'OBSERVADO' => $estadoObservado,
            };

            $papeleta = Papeleta::create([
                'codigo' => sprintf('PAP-%d-%05d-%s', now()->year, $i + 1, Str::upper(Str::random(3))),
                'trabajador_id' => $trabajador->id,
                'jefe_id' => $jefe->id,
                'area_id' => $trabajador->cargo->area_id,
                'sede_id' => $trabajador->sede_id,
                'motivo_id' => $motivos[$i % $motivos->count()]->id,
                'estado_id' => $estadoInicial->id,
                'destino' => ['Municipalidad', 'Clínica San Pablo', 'Banco de la Nación', 'Centro de capacitación', 'Domicilio'][$i],
                'motivo_detalle' => 'Papeleta de prueba #'.($i + 1),
                'fecha_salida' => now()->subDays(5 - $i)->toDateString(),
                'hora_salida_programada' => '09:00:00',
                'hora_retorno_programada' => '13:00:00',
            ]);

            HistorialPapeleta::registrar($papeleta, $trabajador, 'CREADA', null, 'SOLICITADO', 'Papeleta creada por el trabajador (seed)');

            // A partir de SOLICITADO, cada escenario acumula los pasos previos.
            if (in_array($escenario, ['APROBADO_JEFE', 'EN_CURSO', 'FINALIZADO'], true)) {
                FlujoAprobacion::create([
                    'papeleta_id' => $papeleta->id, 'usuario_id' => $jefe->id,
                    'rol' => 'JEFE', 'accion' => AccionFlujo::APROBADO->value,
                    'comentario' => 'Aprobado por el jefe (seed)',
                ]);
                HistorialPapeleta::registrar($papeleta, $jefe, 'APROBADA', 'SOLICITADO', 'APROBADO_JEFE');
            }

            if (in_array($escenario, ['EN_CURSO', 'FINALIZADO'], true)) {
                FlujoAprobacion::create([
                    'papeleta_id' => $papeleta->id, 'usuario_id' => $rrhh->id,
                    'rol' => 'RRHH', 'accion' => AccionFlujo::APROBADO->value,
                    'comentario' => 'Aprobado por RRHH (seed)',
                ]);
                HistorialPapeleta::registrar($papeleta, $rrhh, 'APROBADA', 'APROBADO_JEFE', 'APROBADO_RRHH');

                $vigilante = $vigilantes[$i % $vigilantes->count()];
                Marcacion::create([
                    'papeleta_id' => $papeleta->id, 'tipo' => TipoMarcacion::SALIDA->value,
                    'registrado_por_user_id' => $vigilante->id,
                ]);
                HistorialPapeleta::registrar($papeleta, $vigilante, 'MARCO_SALIDA_VIGILANTE', 'APROBADO_RRHH', 'EN_CURSO', "Confirmado por vigilante: {$vigilante->name} (seed)");
            }

            if ($escenario === 'FINALIZADO') {
                $vigilante = $vigilantes[$i % $vigilantes->count()];
                Marcacion::create([
                    'papeleta_id' => $papeleta->id, 'tipo' => TipoMarcacion::RETORNO->value,
                    'registrado_por_user_id' => $vigilante->id,
                ]);
                HistorialPapeleta::registrar($papeleta, $vigilante, 'MARCO_RETORNO_VIGILANTE', 'EN_CURSO', 'FINALIZADO', "Confirmado por vigilante: {$vigilante->name} (seed)");
            }

            if ($escenario === 'OBSERVADO') {
                FlujoAprobacion::create([
                    'papeleta_id' => $papeleta->id, 'usuario_id' => $jefe->id,
                    'rol' => 'JEFE', 'accion' => AccionFlujo::OBSERVADO->value,
                    'comentario' => 'Falta sustento (seed)',
                ]);
                HistorialPapeleta::registrar($papeleta, $jefe, 'OBSERVADA', 'SOLICITADO', 'OBSERVADO', 'Falta sustento (seed)');

                Observacion::create([
                    'papeleta_id' => $papeleta->id, 'usuario_id' => $jefe->id,
                    'tipo' => TipoObservacion::JUSTIFICACION->value,
                    'comentario' => 'Adjuntar documento que sustente el motivo (seed)',
                    'atendida' => false,
                ]);
            }

            // Un adjunto y una notificación por papeleta, para tener 5 en cada tabla.
            Adjunto::create([
                'papeleta_id' => $papeleta->id,
                'nombre_original' => "sustento_{$papeleta->codigo}.pdf",
                'archivo' => "adjuntos/seed/{$papeleta->id}.pdf",
                'extension' => 'pdf',
                'peso' => 102400,
            ]);

            NotificacionSistema::create([
                'user_id' => $trabajador->id,
                'papeleta_id' => $papeleta->id,
                'tipo' => 'PAPELETA_PENDIENTE',
                'canal' => CanalNotificacion::SISTEMA->value,
                'titulo' => 'Papeleta registrada',
                'mensaje' => "Tu papeleta {$papeleta->codigo} fue registrada correctamente (seed).",
                'enviada_at' => now(),
            ]);
        });

        $this->command->info('Datos de prueba listos (5 registros mínimo por tabla). Password para todos los usuarios: "password"');
        $this->command->table(
            ['Rol', 'Email(s)'],
            [
                ['ADMINISTRADOR', 'admin@demo.test'],
                ['JEFE', 'jefe@demo.test'],
                ['RRHH', 'rrhh@demo.test'],
                ['VIGILANTE', 'vigilante1@demo.test ... vigilante5@demo.test'],
                ['TRABAJADOR', 'trabajador1@demo.test ... trabajador5@demo.test'],
            ]
        );
    }
}
