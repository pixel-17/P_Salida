# Sistema de Papeletas

Sistema web para la gestión de papeletas de salida del personal: solicitud,
aprobación (jefe → RRHH), marcación de salida/retorno en la garita mediante
código/QR, notificaciones y reportes.

## Roles

- **Trabajador** — solicita sus papeletas de salida y muestra el código/QR
  el día autorizado.
- **Jefe** — aprueba o rechaza las solicitudes de su equipo.
- **RRHH** — aprueba en segunda instancia y administra el flujo general.
- **Vigilante** — confirma salida/retorno en la puerta (escaneando el QR,
  buscando por DNI/nombre/código, o ingresando el código a mano).
- **Administrador** — gestión de usuarios, sedes y configuración.

## Requisitos

- PHP 8.2+
- Composer
- Node.js + npm
- Una base de datos  MySQL

## Instalación

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate --seed

npm run build   
php artisan serve
```

## Configuración propia del negocio

Ajustable sin tocar código, desde el panel de administración en `/configuracion`
(solo rol ADMINISTRADOR):

- Horario laboral permitido para solicitar salidas.
- Días no laborables (activar o no el domingo como laborable).

La hora límite en la que la garita puede confirmar salidas/retornos no es un
campo aparte: se calcula automáticamente como el fin del horario laboral + 10
minutos de cortesía (`Configuracion::horaLimiteGarita()`), para evitar
combinaciones inconsistentes entre ambos horarios.

## Tests

```bash
php artisan test
```
