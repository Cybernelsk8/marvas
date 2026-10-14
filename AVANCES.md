# Avances de desarrollo de MARVAS

> Bitácora compartida de estado. Leer este archivo junto con [PLANIFICACION.md](./PLANIFICACION.md) al retomar el trabajo. Actualizarlo al comenzar y al terminar cada tarea de un agente.

## Estado general

- **Última actualización:** 2026-10-13.
- **Estado de fase:** Fase A cerrada; Módulo 2 en curso, con configuración global y calendario interno inicial implementados y validados.
- **Resumen:** Recepción puede gestionar solicitudes desde la bandeja o la agenda, asignar una sede activa, consultar y filtrar citas por periodo/estado/sede y mantener horarios globales. Sigue pendiente definir la asignación de profesional y ampliar las reglas de disponibilidad.
- **Importante:** este resumen refleja los archivos observados en el árbol de trabajo al crear la bitácora, no implica que los cambios estén confirmados en Git ni que la aplicación esté desplegada.

> **Reutilización transversal:** consultar la sección “Componentes, traits, servicios y helpers reutilizables” de `PLANIFICACION.md` antes de construir tablas, calendarios, selectores, gráficos, utilidades o lógica común. En especial, estudiar el contrato de `x-data-table` + `DataTable` + `Searchable` + `HeaderMap` y los ejemplos `@interact` de Admin.

## Mapa por módulo

| Módulo | Estado | Evidencia / situación actual | Próximo resultado |
|---|---|---|---|
| 0. Fundamentos y permisos | Parcial | Hay autenticación, permisos Spatie, áreas, roles, páginas y permisos; existen rutas admin con middleware. Falta definir permisos de cada módulo clínico/operativo. | Aprobar matriz de roles/permisos para recepción, profesionales, caja, inventario y administración. |
| 1. Solicitudes y recepción | Implementado y validado | `App\Livewire\Solicitud` permite editar datos, asignar sede activa al confirmar, reprogramar, cancelar y generar enlaces firmados temporales del formulario de paciente. | Hacer prueba visual con usuarios reales; después decidir historial de llamadas y automatización de mensajes. |
| 2. Agenda y disponibilidad | En curso | `admin.schedule` reutiliza `x-calendar` para vistas día/semana/mes, filtros de estado/sede y modales de confirmar/reprogramar/cancelar; las reglas transaccionales se comparten con Solicitudes mediante `AppointmentManagementService`. `/admin/availability` mantiene las horas globales. | Definir asignación de profesional; evaluar duración, pausas y días no laborables. |
| 3. Pacientes y expediente | Parcial en datos / pendiente en pantallas | Modelos y migraciones `Paciente`, `ExpedienteMaestro`, facturación y domicilios existen; no se encontró una pantalla clínica/paciente específica en Livewire. | Crear búsqueda de pacientes y ficha del expediente sin duplicados. |
| 4. Consultas y evaluación | Parcial en datos / pendiente en pantallas | Tablas/modelos de consultas, anamnesis, evaluaciones e informes existen; no se encontró flujo Livewire de consulta. | Diseñar flujo clínico y formulario general por especialidad. |
| 5. Clasificación y tratamiento | Parcial en datos / pendiente en pantallas | Existen clasificación, plan terapéutico, paquete asociado y evolución en esquema/modelos. | Definir Modelo 6+ con el equipo y crear flujo de clasificación/plan/evolución. |
| 6. Consentimientos y archivos | Parcial en datos / pendiente en pantallas | Hay plantillas/registros de consentimiento, archivos y campos de trazabilidad. | Definir política legal/de almacenamiento y crear pantallas seguras. |
| 7. Paquetes, pagos y facturación | Parcial en datos / pendiente en pantallas | Existen paquetes clínicos/paciente, pagos y datos de facturación. | Acordar reglas financieras e implementar catálogo/registro de cobro. |
| 8. Inventario y compras | Parcial en datos / pendiente en pantallas | Existen productos, lotes, stock por sucursal, órdenes y movimientos. | Acordar reglas de existencias y construir recepción de compra con movimientos atómicos. |
| 9. Notificaciones y recordatorios | Parcial en datos / pendiente en pantallas | Hay tabla/modelo de notificaciones, flag de recordatorio en citas e ideas en `IDEAS.txt`; no se encontró envío operativo. | Elegir canal/proveedor y diseñar registro de intentos y seguridad de enlaces. |
| 10. Dashboard/reportes | Pendiente | `resources/views/dashboard.blade.php` mantiene contenido placeholder. | Definir indicadores por perfil y reemplazar placeholders con datos autorizados. |
| 11. Landing y reserva | Implementado y validado | La reserva pública valida fecha futura, revalida horarios y conflictos dentro de una transacción y crea una cita `solicitada` visible para recepción; existen pruebas para reserva exitosa y horario ocupado. | Decidir detección asistida de pacientes existentes y futura asignación de sede/profesional. |

## Implementado u observado en el repositorio

### Sitio público

- Rutas públicas para inicio, servicios y agendamiento en `routes/landing-page.php` y su registro en `bootstrap/app.php`.
- Vistas de inicio y servicios en `resources/views/landing-page`.
- Formulario Livewire `App\Livewire\LandingPage\AgendarCita`.
- Trait `App\Livewire\Traits\HasCitas` para horarios, eventos, fechas completas y disponibilidad.
- Prueba unitaria existente: `tests/Unit/Livewire/HasCitasTest.php`.

### Solicitudes internas

- Ruta `solicitudes` con autenticación y verificación en `routes/web.php`.
- Componente `App\Livewire\Solicitud` con tarjetas, filtros, paginación y modales para editar datos del paciente, reprogramar y cancelar.
- La edición persiste campos demográficos/contacto validados sin reemplazar el motivo enviado en `notas_recepcion`; `numero_dpi` se valida como único.
- Confirmación vuelve a revisar horario y traslape dentro de una transacción; reprogramación valida fecha futura y disponibilidad; cancelación exige y conserva motivo.
- Los permisos `page.view.requests`, `requests.edit`, `requests.confirm`, `requests.reschedule` y `requests.cancel` se crean con el seeder de acceso. La ruta se protege con `can:page.view.requests`.
- El seeder crea el rol `Recepcionista` y le asigna esos permisos de forma aditiva/idempotente; no crea cuentas de personal.
- La acción “Generar link de formulario” sigue siendo visual y no implementada.
- `AgendarCita` registra la reserva como `solicitada`; recepción la convierte en `confirmada`.

### Seeders de catálogos y demostración

- `CatalogosClinicosSeeder` agrupa los seeders de especialidades, categorías de inventario, sede/profesionales demostrativos, horarios demostrativos y acceso al módulo de solicitudes.
- Los catálogos de especialidad y categoría son idempotentes y corresponden a los valores del sitio público y del enum de inventario.
- Sede, cuentas/perfiles de profesionales y horarios de demostración solo se crean en `local` y `testing`. Los usuarios llevan correos `example.test`, contraseñas aleatorias y no reciben roles.
- Los perfiles demo se enlazan a especialidades y sede porque `profesionales.user_id` es obligatorio y único. Los horarios son datos ficticios, no un horario comercial aprobado.
- `DatabaseSeeder` invoca `CatalogosClinicosSeeder`; **no ejecutar el `DatabaseSeeder` completo en una base compartida o productiva**: conserva lógica anterior no idempotente y datos de desarrollo. Los nuevos seeders agrupados no hacen seguros los seeders preexistentes.

### Administración y plataforma

- Pantallas Livewire para administración de usuarios, detalle de usuario, áreas, páginas, permisos y roles.
- Ajustes Livewire de perfil, apariencia y seguridad.
- Layout interno con sidebar Flux y notificaciones toast.
- `resources/views/dashboard.blade.php` es una estructura inicial, no un dashboard funcional.

### Dominio de datos

- Migraciones para pacientes, expediente, citas, consultas, anamnesis general y por terapia, evaluación MBI, clasificación, planes, evolución, consentimientos, archivos, pagos, paquetes, inventario, sucursales, profesionales, notificaciones y auditoría.
- Modelos Eloquent cubren muchas relaciones y casts; su existencia no demuestra que las operaciones correspondientes estén implementadas en UI.

## Inconsistencias/pendientes inmediatos

1. **Prueba visual operativa:** ingresar con una cuenta de `Recepcionista` a `/solicitudes`, `/admin/schedule` y `/admin/availability`; recorrer gestión de solicitud, calendario y permisos de lectura/edición.
2. **Asignaciones:** las citas todavía pueden no tener profesional ni sede; decidir cuándo se asignan antes de añadir esos filtros o imponer conflictos por recurso.
3. **Identificación y contacto:** definir detección asistida de pacientes existentes, campos obligatorios antes de confirmar y conservación del historial de llamadas/contacto.
4. **Enlace y recordatorios:** el enlace firmado temporal para completar datos ya funciona y se comparte manualmente; faltan historial de contacto y envío automatizado por un canal aprobado.

## Pruebas y verificaciones conocidas

| Alcance | Verificación | Resultado conocido |
|---|---|---|
| Disponibilidad de citas | `tests/Unit/Livewire/HasCitasTest.php` | Pasó en la corrida dirigida de Fase A. |
| Autenticación/perfil/seguridad | Pruebas en `tests/Feature/Auth` y `tests/Feature/Settings` | Existen pruebas; el estado de ejecución actual no se verificó. |
| Seeders y solicitudes | `tests/Feature/Database/CatalogosClinicosSeederTest.php`, `tests/Feature/Livewire/AgendarCitaTest.php`, `tests/Feature/Livewire/SolicitudTest.php` | Pasaron: idempotencia/guardia de demo, reserva pública exitosa y conflicto, edición de paciente, confirmación, solapamiento, reprogramación y cancelación. |
| Agenda interna y configuración global | `tests/Feature/Livewire/ScheduleTest.php`, `AvailabilityTest.php` | Pasaron: permiso, selección de fecha, filtros por día/semana/estado, actualización y validación de horarios. |
| Consultas, expediente, pagos e inventario | No se encontró cobertura de sus pantallas porque no se encontraron tales pantallas | Pendiente al implementar los módulos. |

## Decisiones de negocio

| Tema | Decisión | Responsable | Fecha |
|---|---|---|---|
| Estado inicial de cita web y transición a confirmada | Implementado como decisión técnica de Fase A: `solicitada` al enviar; recepción confirma después de contactar. | Implementación Fase A | 2026-10-13 |
| Disponibilidad global o por profesional/sucursal | Mantener horas globales. Recepción asigna una sede activa al revisar la solicitud y antes de confirmar; la selección no se pide en la landing. La asignación de profesional sigue pendiente. | Usuario | 2026-10-13 |
| Identificación de paciente existente desde solicitud | Pendiente. Evitar coincidencia automática únicamente por nombre. | Por definir | — |
| Campos requeridos antes de confirmar | Pendiente. | Por definir | — |
| Historial de llamadas y resultados de contacto | Pendiente: decidir campos/eventos a conservar. | Por definir | — |
| Canal de confirmaciones y recordatorios | Pendiente: correo, SMS, WhatsApp u otro; revisar consentimiento/costo/proveedor. | Por definir | — |
| Modelo 6+ y reglas de clasificación | Pendiente de validación clínica. | Por definir | — |
| Pagos, devoluciones y reglas fiscales | Pendiente de validación administrativa/contable. | Por definir | — |
| Lotes, caducidad, FEFO y transferencias | Pendiente de validación operativa. | Por definir | — |

## Trabajo activo

> Registrar aquí cada asignación antes de editar. Retirar o mover al historial al terminar. No reclamar archivos ya asignados por otro agente sin coordinar.

| Tarea | Agente/persona | Módulo | Alcance/archivos reservados | Estado | Dependencias |
|---|---|---|---|---|---|
| — | — | — | Ninguna tarea registrada al crear esta bitácora. | — | — |

## Próximos pasos recomendados

1. **Hacer prueba visual operativa** del ciclo landing → solicitud → recepción → agenda con cuenta `Recepcionista`; verificar que solo Sysadmin edite horarios.
2. **Agregar acciones de gestión desde la agenda** reutilizando los flujos existentes en Solicitudes; definir el flujo de asignación de profesional antes de agregar ese filtro o reglas de traslape por profesional.
3. **Definir detección asistida de pacientes existentes**, registro de contacto y campos requeridos para confirmar.
4. **Aprobar canal de comunicación y consentimiento** para recordatorios/enlaces; después implementar historial y envío.
5. **Crear búsqueda/ficha de paciente** y comenzar el flujo de consulta clínica.

## Registro cronológico de sesiones/agentes

### 2026-10-13 — Análisis inicial y creación de documentación

- **Resultado:** se documentó la interpretación de producto y el plan modular en `PLANIFICACION.md`; esta bitácora inicializa el estado observado.
- **Análisis:** MARVAS está orientado a clínica de medicina biológica/integrativa; el esquema cubre ciclo clínico y operación, mientras que las pantallas clínicas y administrativas especializadas aún deben construirse.
- **Siguiente paso:** resolver decisiones del flujo de solicitudes y asumir el primer módulo de recepción.
- **Pruebas ejecutadas para esta documentación:** ninguna; son cambios solo de documentación.

### 2026-10-13 — Inventario de piezas reutilizables

- **Resultado:** se amplió `PLANIFICACION.md` con contratos y pautas de reutilización para componentes anónimos (`calendar`, `chart`, `date-picker`, `qr`, `select`, `data-table`), `@interact`, traits, servicios, helpers y `HeaderMap`.
- **Ejemplos revisados:** componentes y vistas de usuarios, roles, permisos, páginas, áreas, solicitudes y perfil; `DataTable`, `Searchable`, `Interact`, `Charts`, `HasCitas`, servicios y `app/Helpers/global.php`.
- **Siguiente paso:** los agentes deben inspeccionar los ejemplos y sus usos antes de crear piezas duplicadas; los cambios compartidos a filtros, tabla o helpers deben probar regresiones en las pantallas existentes.
- **Pruebas ejecutadas para esta documentación:** ninguna; son cambios solo de documentación.

### 2026-10-13 — Inicio de Fase A y catálogos

- **Agente/persona:** Copilot.
- **Objetivo:** iniciar solicitudes/recepción y crear catálogos y datos demo previos al resto de módulos.
- **Cambios realizados:** seeders idempotentes para especialidades y categorías; datos demo de sede/profesionales/horarios con guardia `local`/`testing`; permisos y página de solicitudes; reserva pública con estado `solicitada`; recepción con edición, confirmación, reprogramación y cancelación; validación de disponibilidad con transacciones; pruebas Feature para seeders, reserva pública y solicitudes.
- **Archivos modificados:** `database/seeders`, `app/Livewire/LandingPage/AgendarCita.php`, `app/Livewire/Solicitud.php`, `app/Livewire/Traits/HasCitas.php`, `app/Models/Cita.php`, `resources/views/livewire/solicitud.blade.php`, `routes/web.php` y `tests/Feature`.
- **Pruebas/comandos y resultado:** tras habilitar temporalmente las DLL SQLite solo en el proceso CLI, pasaron los 12 tests dirigidos (47 assertions) de catálogos, landing, recepción y disponibilidad. También pasaron `php -l`, Pint, compilación Blade y registro de la ruta. La extensión no se habilitó permanentemente en el `php.ini`.
- **Decisiones tomadas (responsable/fecha):** el usuario autorizó perfiles demo ligados a cuentas ficticias; solo se generan en `local`/`testing`, usan `example.test`, contraseña aleatoria y no reciben roles. Horarios y sede son datos ilustrativos. Para Fase A se mantiene disponibilidad global sin asignación obligatoria de profesional/sede.
- **Pendientes o bloqueos:** falta prueba visual manual; “Generar link de formulario”, agenda interna e historial de contacto siguen pendientes.
- **Próximo paso recomendado:** confirmar en navegador el acceso al menú/acciones con una cuenta asignada a `Recepcionista`, y después acordar alcance de agenda.
- **Estado del trabajo:** implementado y validado (prueba visual pendiente).

### 2026-10-13 — Cierre y validación dirigida de Fase A

- **Cambios adicionales para validar:** `phpunit.xml` fija `APP_URL=http://localhost` para que las pruebas no antepongan la ruta local `/marvas/public`; migraciones usan el tipo SQLite compatible en pruebas y mantienen las restricciones CHECK en MariaDB/MySQL; `sidebar.blade.php` usa un closure local en lugar de declarar una función global repetidamente. Se corrigió el tipo de fecha para aceptar `CarbonImmutable`.
- **Pruebas:** `CatalogosClinicosSeederTest`, `AgendarCitaTest`, `SolicitudTest` y `HasCitasTest`: 13 pruebas y 48 assertions, todas pasaron mediante PHPUnit directo con las DLL SQLite habilitadas solo para ese proceso. Se verificó también que un usuario sin permiso no pueda acceder a solicitudes.
- **Validaciones adicionales:** `php -l` en los PHP modificados, Pint, `git diff --check`, compilación Blade y `route:list --path=solicitudes -vv`; todos pasaron. La ruta confirma el middleware `Authorize:page.view.requests`.
- **Alcance validado:** seeders idempotentes y restricción de datos demo por ambiente; nueva solicitud pública, bloqueo de horario ocupado; guardado de datos de paciente sin perder motivo; confirmación, conflictos, reprogramación, cancelación y denegación de acceso sin permiso.
- **Pendiente:** verificación visual manual con una cuenta real del rol `Recepcionista`; no se ejecutó el `DatabaseSeeder` completo.
- **Estado de Fase A:** implementado y validado por pruebas dirigidas.

### 2026-10-13 — Corrección de confirmación y formulario del paciente

- **Objetivo:** corregir la confirmación de citas desde recepción, eliminar confirmaciones nativas del navegador y activar la opción de compartir un formulario de datos del paciente.
- **Causa encontrada:** confirmar exigía volver a encontrar la configuración `horarios_citas`; esa configuración demo solo se crea en `local`/`testing`. Si faltaba, no se actualizaba el estado y la vista tampoco mostraba el error agregado. La opción de generar link era únicamente un elemento visual sin acción conectada.
- **Cambios realizados:** la confirmación modal revalida el estado, la fecha futura y los traslapes antes de cambiar a `confirmada`; ya no depende de la configuración horaria actual. Los errores y éxitos se informan con toasts Flux. Se agregó un formulario público para completar/actualizar los datos del paciente y un enlace firmado que vence en 7 días, con acciones para copiarlo o compartirlo por WhatsApp. Un enlace firmado inválido o vencido no abre el formulario.
- **Archivos modificados:** `app/Livewire/Solicitud.php`, `app/Livewire/LandingPage/FormularioPaciente.php`, `resources/views/livewire/solicitud.blade.php`, `resources/views/livewire/landing-page/formulario-paciente.blade.php`, `routes/web.php` y pruebas Feature de solicitudes/formulario.
- **Pruebas/comandos y resultado:** 15 pruebas y 49 assertions pasaron en `SolicitudTest`, `FormularioPacienteTest`, `AgendarCitaTest` y `HasCitasTest`; Pint, compilación Blade, `git diff --check` y registro de la ruta firmada también pasaron.
- **Pendientes:** prueba visual manual con una cuenta de recepción y prueba en móvil del enlace compartido; historial de contacto y envío automático no están incluidos.
- **Estado del trabajo:** implementado y validado automáticamente.

### 2026-10-13 — Configuración de disponibilidad global

- **Decisión funcional:** mantener un horario global compartido por toda la clínica; por ahora no separar por sede ni profesional.
- **Cambios realizados:** nueva pantalla administrativa para consultar y editar las horas globales, agregar/quitar horarios y guardar la lista validada y ordenada. La ruta requiere `page.view.schedule`; modificar requiere `schedule.edit`. El seeder registra navegación/permisos y ahora solo crea los horarios demo si no existe configuración, evitando sobrescribir cambios hechos por administración.
- **Archivos:** `app/Livewire/Admin/Availability.php`, `resources/views/livewire/admin/availability.blade.php`, `database/seeders/AgendaAccessSeeder.php`, `database/seeders/CatalogosClinicosSeeder.php`, `database/seeders/HorariosCitasDemoSeeder.php`, `routes/admin.php` y `tests/Feature/Livewire/AvailabilityTest.php`.
- **Acceso/configuración inicial:** ejecutar `php artisan db:seed --class=CatalogosClinicosSeeder` en la base objetivo para registrar navegación y permisos. Sysadmin recibe acceso; la pantalla queda en el menú “Agenda” → “Disponibilidad”. Los horarios demo se cargan solo en `local`/`testing` si todavía no existe el ajuste. Los valores aprobados pueden editarse desde esta pantalla.
- **Validación:** suite enfocada de disponibilidad, solicitudes, landing y `HasCitas`: 17 pruebas y 56 assertions pasaron. Pint, `php -l`, compilación Blade, ruta y `git diff --check` pasaron.
- **Siguiente paso:** construir la agenda interna usando `x-calendar` y la misma fuente global; mantener la decisión de disponibilidad global.
- **Estado del trabajo:** panel de configuración implementado y validado; módulo 2 continúa en curso.

### 2026-10-13 — Calendario interno de citas

- **Objetivo:** avanzar el Módulo 2 con una agenda protegida para recepción y personal con permiso, manteniendo disponibilidad global.
- **Cambios realizados:** ruta `/admin/schedule` con navegación Agenda → Agenda de citas; calendario mensual reutilizable `x-calendar`, vistas Día/Semana/Mes, navegación entre periodos, selector de estado y resumen cronológico del paciente, contacto, motivo y asignaciones existentes. El calendario visualiza citas para los últimos 3 y próximos 6 meses; el detalle muestra citas del día o semana seleccionados. Recepcionista obtiene permiso de lectura de agenda; la edición de horarios permanece restringida a `schedule.edit`.
- **Archivos:** `app/Livewire/Admin/Schedule.php`, `resources/views/livewire/admin/schedule.blade.php`, `database/seeders/AgendaAccessSeeder.php`, `routes/admin.php`, `tests/Feature/Livewire/ScheduleTest.php` y `tests/Feature/Database/CatalogosClinicosSeederTest.php`.
- **Pruebas/comandos y resultado:** suite dirigida de agenda, configuración, catálogos, solicitudes, formulario público, reserva y disponibilidad: 25 pruebas y 92 assertions pasaron. Pint, sintaxis PHP, compilación Blade, middleware de rutas y `git diff --check` pasaron.
- **Pendientes:** agenda de lectura por ahora; no crea, edita ni mueve citas. No hay filtros de profesional/sede porque las nuevas citas aún no se asignan obligatoriamente. Se debe confirmar visualmente con recepción y acordar asignaciones antes de añadir esos filtros.
- **Estado del trabajo:** primera pantalla de agenda interna implementada y validada; Módulo 2 continúa en curso.

### 2026-10-13 — Asignación de sede al confirmar solicitudes

- **Decisión de negocio:** recepción elige una sede activa al confirmar la cita. La landing conserva el flujo actual y no obliga al paciente a seleccionar sede.
- **Cambios realizados:** el modal “Confirmar cita” ofrece sedes activas, precarga la sede si ya estaba asignada y bloquea la confirmación si no hay una sede activa o no se seleccionó una. El backend valida que exista y siga activa dentro de la transacción antes de actualizar `sucursal_id` y estado; las tarjetas muestran la sede o “Pendiente de asignar”.
- **Archivos:** `app/Livewire/Solicitud.php`, `resources/views/livewire/solicitud.blade.php`, `tests/Feature/Livewire/SolicitudTest.php`, `PLANIFICACION.md` y `AVANCES.md`.
- **Validación:** suite dirigida de solicitudes, agenda, disponibilidad, catálogos, formulario público y reserva: 27 pruebas y 99 assertions pasaron. Se cubren confirmación con sede, rechazo sin sede y rechazo con sede inactiva; Pint, sintaxis, vistas Blade, rutas y `git diff --check` pasaron.
- **Corrección posterior:** la confirmación ahora usa un formulario Livewire: el selector Flux usa `wire:model` y el envío ejecuta `confirmar` en la misma petición, evitando que la selección y el clic compitan en peticiones separadas.
- **Regresión:** las pruebas envían el valor del selector junto con la acción de confirmar en una sola petición Livewire, como hace el formulario; 10 pruebas y 37 assertions pasan, junto con Pint, sintaxis PHP, compilación de vistas y `git diff --check`.
- **Próximo paso:** verificar visualmente el flujo en navegador y mostrar la sede asignada en agenda; definir asignación de profesional por separado.
- **Estado del trabajo:** implementado y validado automáticamente; prueba visual manual pendiente.

### 2026-10-13 — Filtro por sede en la agenda

- **Objetivo:** permitir que recepción consulte citas por sede sin cambiar la disponibilidad, que sigue siendo global.
- **Cambios realizados:** la agenda permite filtrar por todas las sedes, una sede concreta o citas sin sede. El filtro afecta tanto la lista de citas como los indicadores del calendario; se muestran sedes inactivas con esa condición para conservar acceso al historial.
- **Archivos:** `app/Livewire/Admin/Schedule.php`, `resources/views/livewire/admin/schedule.blade.php`, `tests/Feature/Livewire/ScheduleTest.php`, `PLANIFICACION.md` y `AVANCES.md`.
- **Pruebas/comandos y resultado:** `ScheduleTest`: 6 pruebas y 27 assertions; Pint, sintaxis PHP, compilación de vistas y `git diff --check` pasan.
- **Decisiones tomadas:** el filtro no limita la agenda a sedes activas porque las citas históricas vinculadas a una sede inactiva deben seguir siendo consultables.
- **Próximo paso:** definir el flujo de asignación de profesional antes de añadir ese filtro o reglas de traslape por profesional.
- **Estado del trabajo:** filtro por sede implementado y validado.

### 2026-10-13 — Acciones de gestión desde la agenda

- **Objetivo:** permitir a recepción confirmar, reprogramar y cancelar citas desde el calendario, manteniendo las mismas validaciones y reglas que Solicitudes.
- **Cambios realizados:** se agregaron acciones contextuales en las citas y modales Flux para confirmar (con sede activa obligatoria), reprogramar y cancelar con motivo. Se extrajeron las operaciones transaccionales a `AppointmentManagementService`, consumido ahora tanto por la agenda como por Solicitudes; así se conserva una única implementación de las reglas de estado, horario, colisión y sede activa. Los modales avisan y bloquean la confirmación cuando no hay sedes activas.
- **Archivos:** `app/Services/AppointmentManagementService.php`, `app/Livewire/Admin/Schedule.php`, `resources/views/livewire/admin/schedule.blade.php`, `app/Livewire/Solicitud.php`, `tests/Feature/Livewire/ScheduleTest.php`, `PLANIFICACION.md` y `AVANCES.md`.
- **Pruebas/comandos y resultado:** pruebas de agenda y solicitudes: 19 pruebas y 72 assertions; Pint, sintaxis PHP, compilación de vistas y `git diff --check` pasan.
- **Decisiones tomadas:** las acciones de agenda reutilizan el servicio transaccional compartido y conservan los permisos `requests.confirm`, `requests.reschedule` y `requests.cancel`; solo las solicitudes pendientes admiten reprogramación o cancelación.
- **Próximo paso:** definir cuándo y cómo recepción asignará un profesional; después añadir filtros y conflictos por profesional solo si esa decisión cambia el modelo operativo.
- **Estado del trabajo:** acciones de agenda implementadas y validadas automáticamente; queda pendiente verificación visual.

### 2026-10-13 — Corrección del ENUM de tipo de cita en MariaDB

- **Problema:** el landing enviaba los valores `primera_vez` y `evaluacion_integral`, pero la base MariaDB tenía un ENUM anterior con `primera vez` y `evaluacion integral`, lo que causaba `Data truncated for column 'tipo_cita'`.
- **Cambios realizados:** se agregó una migración compatible que amplía temporalmente el ENUM, normaliza los valores existentes con espacios a sus claves con guion bajo y deja la columna con los mismos valores canónicos que el código y la migración original.
- **Archivos:** `database/migrations/2026_10_13_230500_normalize_tipo_cita_enum.php`.
- **Aplicación:** migración ejecutada sobre la base configurada; se verificó que `citas.tipo_cita` ahora permite `primera_vez`, `seguimiento`, `control`, `evaluacion_integral` y `procedimiento`.
- **Pruebas/comandos y resultado:** `AgendarCitaTest`: 2 pruebas y 11 assertions; Pint, sintaxis PHP y `git diff --check` pasan.
- **Próximo paso:** volver a enviar una solicitud desde la landing y confirmar que se guarda como pendiente sin truncamiento.
- **Estado del trabajo:** corregido y aplicado en la base local configurada.

## Plantilla para registrar una entrega

Copiar al final del registro cronológico y completar al terminar cada tarea:

```text
### AAAA-MM-DD — [módulo/tarea]
- Agente/persona:
- Objetivo:
- Cambios realizados:
- Archivos modificados:
- Pruebas/comandos y resultado:
- Decisiones tomadas (responsable/fecha):
- Pendientes o bloqueos:
- Próximo paso recomendado:
- Estado del trabajo: pendiente / en curso / bloqueado / parcial / completo
```
