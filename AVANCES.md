# Avances de desarrollo de MARVAS

> Bitácora compartida de estado. Leer este archivo junto con [PLANIFICACION.md](./PLANIFICACION.md) al retomar el trabajo. Actualizarlo al comenzar y al terminar cada tarea de un agente.

## Estado general

- **Última actualización:** 2026-10-24.
- **Estado de fase:** además de las funciones clínicas y del Evaluador MBI por sede ya descritas, se implementaron CRUD administrativos para sedes, especialidades y profesionales, así como la creación de citas desde Agenda. La cita interna requiere paciente activo con expediente, se revalida en transacción y queda `confirmada`; primera visita exige al Evaluador MBI de la sede. Sysadmin gestiona los catálogos y las cuentas/roles; Recepción y Sysadmin pueden crear citas. Las pruebas enfocadas de catálogos, agenda y seeder pasaron: 27 pruebas y 112 assertions.
- **Resumen:** Recepción gestiona solicitudes desde la bandeja o la agenda, asigna sede y profesional al confirmar y filtra por periodo/estado/sede/profesional. Disponibilidad global usa duración configurable; al reprogramar se elige fecha y una hora libre, se permite el horario de la cita actual sin auto-traslape y solo se ofrecen inicios futuros. El directorio usa `DataTable` y `Searchable`; alta/edición y recepción sugieren coincidencias. Reutilizar desde solicitudes o fusionar manualmente requiere confirmación; el sistema traslada relaciones/historial en transacción, deja el origen inactivo y enlazado y audita los cambios y las decisiones sobre conflictos. Las evaluaciones integrales se guardan con auditoría; el cierre no persiste borradores de formularios sin guardar; el IMC se calcula en base de datos; reabrir devuelve la consulta a `en_proceso`.
- **Importante:** este resumen refleja los archivos observados en el árbol de trabajo al crear la bitácora, no implica que los cambios estén confirmados en Git ni que la aplicación esté desplegada.

> **Reutilización transversal:** consultar la sección “Componentes, traits, servicios y helpers reutilizables” de `PLANIFICACION.md` antes de construir tablas, calendarios, selectores, gráficos, utilidades o lógica común. En especial, estudiar el contrato de `x-data-table` + `DataTable` + `Searchable` + `HeaderMap` y los ejemplos `@interact` de Admin.

## Mapa por módulo

| Módulo | Estado | Evidencia / situación actual | Próximo resultado |
|---|---|---|---|
| 0. Fundamentos y permisos | Parcial | Hay autenticación y permisos Spatie. Los catálogos clínicos quedan restringidos a Sysadmin; Recepción y Sysadmin tienen el permiso de crear citas directas desde Agenda. | Aprobar matriz de roles/permisos restante para profesionales, caja e inventario. |
| 1. Solicitudes y recepción | Implementado y validado | `App\Livewire\Solicitud` permite editar datos, asignar sede y Evaluador MBI en primera visita, reprogramar y cancelar. | Hacer prueba visual con usuarios reales; después decidir historial de llamadas y automatización de mensajes. |
| 2. Agenda y disponibilidad | Implementado y validado automáticamente | `admin.schedule` permite confirmar solicitudes y citas directas. Recepción crea citas confirmadas desde la agenda; el servicio revalida horario global, expediente y asignaciones en transacción. Primera visita requiere Evaluador MBI configurado por sede. | Hacer prueba visual en recepción y atender incidencias operativas. |
| 3. Pacientes y expediente | Implementado; cierre de módulo pendiente | `/admin/patients` ofrece directorio/ficha y fusión manual; el expediente tiene citas y secciones clínicas, documentos/consentimientos y pagos/paquetes protegidos por permisos. El usuario confirmó visualmente las secciones. | Completar pruebas visuales de integración y cerrar el módulo con el flujo clínico inicial. |
| 4. Consultas y evaluación | Implementado parcialmente; pruebas dirigidas pasan | La anamnesis integral guarda los incisos 01–06; Acupuntura es individual; el resultado del evaluador y la evaluación MBI se muestran en la ficha. MBI se edita por consulta, guarda listas JSON y audita nombres de campos sin registrar valores clínicos. Cierre/reapertura conservan permisos y comportamiento. | Construir informe por sistemas, test de intoxicación y mapas corporales como componentes separados; definir catálogos clínicos del Modelo 6+ antes de restringir esas selecciones. |
| 5. Clasificación y tratamiento | Parcial en datos / pendiente en pantallas | Existen clasificación, plan terapéutico, paquete asociado y evolución en esquema/modelos. | Definir Modelo 6+ con el equipo y crear flujo de clasificación/plan/evolución. |
| 6. Consentimientos y archivos | Parcial en datos / pendiente en pantallas | Hay plantillas/registros de consentimiento, archivos y campos de trazabilidad. | Definir política legal/de almacenamiento y crear pantallas seguras. |
| 7. Paquetes, pagos y facturación | Parcial en datos / pendiente en pantallas | Existen paquetes clínicos/paciente, pagos y datos de facturación. | Acordar reglas financieras e implementar catálogo/registro de cobro. |
| 8. Inventario y compras | Parcial en datos / pendiente en pantallas | Existen productos, lotes, stock por sucursal, órdenes y movimientos. | Acordar reglas de existencias y construir recepción de compra con movimientos atómicos. |
| 9. Notificaciones y recordatorios | Parcial en datos / pendiente en pantallas | Hay tabla/modelo de notificaciones, flag de recordatorio en citas e ideas en `IDEAS.txt`; no se encontró envío operativo. | Elegir canal/proveedor y diseñar registro de intentos y seguridad de enlaces. |
| 10. Dashboard/reportes | Pendiente | `resources/views/dashboard.blade.php` mantiene contenido placeholder. | Definir indicadores por perfil y reemplazar placeholders con datos autorizados. |
| 11. Landing y reserva | Implementado y validado por pruebas | La landing no presenta selector de tipo y fuerza `primera_vez`; valida fecha futura y horarios/traslapes dentro de una transacción, y crea una cita `solicitada`. El seguimiento se agenda únicamente por personal. | Decidir detección asistida de pacientes existentes y futura asignación de sede/profesional. |
| 12. Catálogos clínicos administrativos | Implementado y validado automáticamente | Hay páginas Livewire de sedes, especialidades y profesionales. Se conservan historiales al desactivar; profesionales enlazan una cuenta ya existente y sincronizan sede/especialidad principal. Sysadmin gestiona el catálogo y la cuenta/rol fuera del CRUD. | Validar visualmente con Sysadmin. |

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

- `CatalogosClinicosSeeder` agrupa los seeders de especialidades, categorías de inventario, sede/profesionales demostrativos, Evaluador MBI por sede, horarios demostrativos y accesos a módulos.
- `AdminAccessSeeder` mantiene de forma idempotente el área Gerencia, las páginas/permisos administrativos y es invocado por `DatabaseSeeder`.
- `HorariosCitasDemoSeeder` crea en `local`/`testing` el horario inicial de lunes a viernes mediante horas individuales de 08:00 a 16:00, omitiendo 13:00 como pausa; sábado/domingo comienzan cerrados. No sobrescribe configuración existente.
- Los catálogos de especialidad y categoría son idempotentes y corresponden a los valores del sitio público y del enum de inventario.
- Sede, cuentas/perfiles de profesionales y horarios de demostración solo se crean en `local` y `testing`. Los usuarios llevan correos `example.test`, contraseñas aleatorias y no reciben roles.
- Los perfiles demo se enlazan a especialidades y sede porque `profesionales.user_id` es obligatorio y único. Los horarios son datos ficticios, no un horario comercial aprobado.
- La especialidad `evaluacion-mbi` y el seeder `EvaluadoresMbiDemoSeeder` asignan un evaluador en la sede demo solo en `local`/`testing`; la configuración operativa se mantiene por sede desde Disponibilidad.
- `DatabaseSeeder` invoca `AdminAccessSeeder` y `CatalogosClinicosSeeder`; **no ejecutar el `DatabaseSeeder` completo en una base compartida o productiva**: conserva lógica anterior no idempotente y datos de desarrollo. Los nuevos seeders agrupados no hacen seguros los seeders preexistentes.
- La disponibilidad ya no lee la configuración antigua `horarios_citas`; las reglas activas se almacenan en `disponibilidad_global_citas`. `settings.value` es `TEXT` desde `2026_10_09_213107_create_settings_table.php`, incluida la capacidad de guardar cierres.
- El 2026-10-22, seis migraciones incrementales de columnas/ajustes se consolidaron en sus `create_*`. La base local se reconstruyó con `migrate:fresh --seed`; las referencias a migraciones incrementales en entradas cronológicas anteriores describen el momento en que se introdujeron, no archivos que sigan presentes.

### Administración y plataforma

- La agenda permite filtrar citas y eventos del calendario por sede y profesional, incluyendo citas que aún no tienen profesional asignado.
- La pantalla Disponibilidad permite definir grupos de días sin solapamiento y añadir/quitar horas de inicio manualmente para cada grupo. Si un día no está asignado o no tiene horas, queda cerrado; cada cita dura 60 minutos, por lo que las horas dentro de un grupo no pueden traslaparse. Se conservan cierres excepcionales por día/intervalo y se rechazan guardados que dejen citas futuras fuera del horario.
- La misma pantalla permite asignar por sede un profesional activo con especialidad Evaluación MBI. Confirmar o completar la asignación de una cita `primera_vez` requiere en el servidor que el profesional coincida con el asignado a la sede y que la especialidad sea `evaluacion-mbi`; la Agenda y Solicitudes filtran/etiquetan ese profesional.
- Pantallas Livewire para administración de usuarios, detalle de usuario, áreas, páginas, permisos y roles.
- Ajustes Livewire de perfil, apariencia y seguridad.
- Layout interno con sidebar Flux y notificaciones toast.
- `resources/views/dashboard.blade.php` es una estructura inicial, no un dashboard funcional.

### Dominio de datos

- Migraciones para pacientes, expediente, citas, consultas, anamnesis general y por terapia, evaluación MBI, clasificación, planes, evolución, consentimientos, archivos, pagos, paquetes, inventario, sucursales, profesionales, notificaciones y auditoría.
- Modelos Eloquent cubren muchas relaciones y casts; su existencia no demuestra que las operaciones correspondientes estén implementadas en UI.

## Inconsistencias/pendientes inmediatos

1. **Módulo 4:** validar visualmente cierre y reapertura; definir los formularios clínicos que seguirán.
2. **Asignaciones:** la confirmación desde agenda/solicitudes asigna sede, profesional y especialidad; si ya hay citas confirmadas con asignación incompleta, la agenda ofrece «Completar asignación» sin cambiar su estado.
3. **Contacto y recordatorios:** faltan historial de contacto y envío automatizado por un canal aprobado.
4. **Operación administrativa:** verificar visualmente los tres CRUD y el modal de cita interna con usuarios autorizados.

## Pruebas y verificaciones conocidas

| Alcance | Verificación | Resultado conocido |
|---|---|---|
| Disponibilidad de citas | `tests/Unit/Livewire/HasCitasTest.php` | Pasó en la corrida dirigida de Fase A. |
| Autenticación/perfil/seguridad | Pruebas en `tests/Feature/Auth` y `tests/Feature/Settings` | Existen pruebas; el estado de ejecución actual no se verificó. |
| Seeders y solicitudes | `tests/Feature/Database/CatalogosClinicosSeederTest.php`, `tests/Feature/Livewire/AgendarCitaTest.php`, `tests/Feature/Livewire/SolicitudTest.php` | Pasaron: idempotencia/guardia de demo, reserva pública exitosa y conflicto, edición de paciente, confirmación, solapamiento, reprogramación y cancelación. |
| Agenda interna y configuración global | `tests/Feature/Livewire/ScheduleTest.php`, `AvailabilityTest.php` | Pasaron: permiso, selección de fecha, filtros por día/semana/estado, actualización y validación de horarios. |
| Apertura inicial de consultas | `ConsultationTest`, `PatientDirectoryTest` y `CatalogosClinicosSeederTest` | Pasaron en esta entrega: 20 pruebas y 134 assertions; Pint y `git diff --check` pasaron. |
| Asignación al confirmar citas | `ScheduleTest` y `SolicitudTest` | Pasaron tras resolver la selección de opciones únicas: 28 pruebas y 123 assertions. |
| Ficha de pacientes y aislamiento de secciones | `PatientDirectoryTest` y `CatalogosClinicosSeederTest` | Pasaron en la entrega anterior: 16 pruebas y 118 assertions; Pint, caché de vistas Blade y `git diff --check` también pasaron. El usuario confirmó verificación visual. |
| Catálogos, cita directa y regresión de agenda/seeders | `tests/Feature/Livewire/ClinicalCatalogsTest.php`, `ScheduleTest.php`, `tests/Feature/Database/CatalogosClinicosSeederTest.php` | Pasaron: 27 pruebas y 112 assertions; Pint, PHP lint, caché de vistas y `git diff --check` también pasaron. |

## Decisiones de negocio

| Tema | Decisión | Responsable | Fecha |
|---|---|---|---|
| Estado inicial de cita web y transición a confirmada | Implementado como decisión técnica de Fase A: `solicitada` al enviar; recepción confirma después de contactar. | Implementación Fase A | 2026-10-13 |
| Asignación antes de confirmar | En recepción, confirmar requiere seleccionar sede, profesional activo de esa sede y especialidad activa asociada al profesional. La disponibilidad de horarios continúa siendo global. | Implementación / pendiente de confirmación operativa | 2026-10-14 |
| Identificación de paciente existente desde solicitud | Coincidencias sugeridas; recepción elige si reutiliza. Nunca fusionar automáticamente. | Usuario | 2026-10-14 |
| Visibilidad de secciones de expediente | Permisos independientes `patients.clinical.view`, `patients.documents.view` y `patients.billing.view`; sin asignación automática a roles nominales. Sysadmin administra asignaciones con Spatie. | Usuario | 2026-10-14 |
| Apertura de consulta | Solo desde cita confirmada con sede, profesional y especialidad asignados; abre en estado `abierta`, conserva la cita sin modificar y requiere `consultations.create`. | Implementación / pendiente de confirmación operativa | 2026-10-14 |
| Campos requeridos antes de confirmar | Pendiente. | Por definir | — |
| Historial de llamadas y resultados de contacto | Pendiente: decidir campos/eventos a conservar. | Por definir | — |
| Canal de confirmaciones y recordatorios | Pendiente: correo, SMS, WhatsApp u otro; revisar consentimiento/costo/proveedor. | Por definir | — |
| Modelo 6+ y reglas de clasificación | Pendiente de validación clínica. | Por definir | — |
| Cuenta de profesional y reserva interna | Sysadmin crea la cuenta, asigna rol/permisos y luego la vincula al perfil profesional. La cita concertada directamente por recepción queda `confirmada`. | Usuario | 2026-10-24 |
| Pagos, devoluciones y reglas fiscales | Pendiente de validación administrativa/contable. | Por definir | — |
| Lotes, caducidad, FEFO y transferencias | Pendiente de validación operativa. | Por definir | — |

## Trabajo activo

> Registrar aquí cada asignación antes de editar. Retirar o mover al historial al terminar. No reclamar archivos ya asignados por otro agente sin coordinar.

| Tarea | Agente/persona | Módulo | Alcance/archivos reservados | Estado | Dependencias |
|---|---|---|---|---|---|
| — | — | — | Ninguna tarea activa registrada. | — | — |

## Próximos pasos recomendados

1. Terminar la validación automatizada y probar visualmente los CRUD y el modal de cita interna con usuarios autorizados.
2. Implementar informe por sistemas, test de intoxicación y mapas corporales como componentes separados.
3. Definir con el equipo clínico catálogos de sistemas y la selección operativa del Modelo 6+.
4. Definir historial de contacto y canal de comunicación antes de automatizar confirmaciones/recordatorios.

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

### 2026-10-14 — Reglas globales de horario y cierres excepcionales

- **Objetivo:** ampliar el Módulo 2 para que disponibilidad pública, confirmaciones y reprogramaciones respeten un único horario global y sus excepciones.
- **Decisiones funcionales:** lunes a viernes; 08:00–17:00; citas fijas de 60 minutos; pausa diaria 13:00–14:00; permitir cierres de un día o intervalos. Impedir guardar cambios que dejen citas futuras no canceladas/no asistió fuera del nuevo horario; nunca mover ni cancelar citas automáticamente.
- **Cambios realizados:** nuevo `ClinicAvailabilityService` como fuente compartida para generar horas y validar día laborable, duración, pausa y cierre. La pantalla Disponibilidad configura días, apertura/cierre, pausa y cierres. Landing, confirmación y reprogramación revalidan contra las mismas reglas dentro de transacción. La configuración se guarda en `disponibilidad_global_citas`; los seeders demo crean los valores iniciales solo si no existe una configuración.
- **Migración:** `2026_10_14_154200_expand_settings_value_column` amplía `settings.value` a `TEXT`, necesario para guardar listas de cierres.
- **Pruebas/comandos y resultado:** suite dirigida `AvailabilityTest`, `AgendarCitaTest`, `ScheduleTest`, `SolicitudTest`, `HasCitasTest` y `CatalogosClinicosSeederTest`: 35 pruebas y 132 assertions pasaron.
- **Aplicación local:** se ejecutó la migración de expansión de `settings.value` y el seeder idempotente `HorariosCitasDemoSeeder` en el entorno local.
- **Pendientes:** validar visualmente los filtros, el calendario, el horario y la gestión de cierres con recepción.
- **Próximo paso recomendado:** abrir `/admin/availability`, confirmar los valores (08:00–17:00 y pausa 13:00–14:00) y verificar una reserva pública y un cierre; luego decidir si se completa la verificación visual del Módulo 2 o se inicia Módulo 3 (Pacientes y expediente).
- **Estado del trabajo:** implementado y validado automáticamente; migración y configuración demo aplicadas localmente, prueba visual manual pendiente.

### 2026-10-14 — Horarios manuales agrupados por días

- **Objetivo:** sustituir los rangos diarios de apertura/cierre por listas manuales de horas de inicio que pueden compartirse entre grupos de días.
- **Decisión funcional:** el administrador selecciona uno o varios días por grupo y agrega las horas individualmente. Un día no puede aparecer en más de un grupo; un día no asignado o un grupo sin horas queda cerrado. Sábado y domingo pueden tener horarios independientes. Se conserva la duración fija de 60 minutos y los cierres excepcionales.
- **Cambios realizados:** la pantalla de disponibilidad permite crear/eliminar grupos, asignar días y agregar/quitar horas. `ClinicAvailabilityService` aplica la lista correspondiente al día en agenda, landing, confirmación y reprogramación, y conserva compatibilidad convirtiendo al vuelo la configuración anterior. Cada grupo valida sus propias horas: permite repetir los mismos horarios entre grupos con días diferentes, pero rechaza repeticiones o traslapes dentro del grupo; también bloquea cambios incompatibles con citas futuras.
- **Pruebas/comandos y resultado:** suite dirigida `AvailabilityTest`, `AgendarCitaTest`, `ScheduleTest`, `SolicitudTest`, `HasCitasTest` y `CatalogosClinicosSeederTest`: 40 pruebas y 149 assertions pasaron; Pint, compilación de vistas y `git diff --check` pasaron.
- **Pendientes:** prueba visual ampliada de una reserva de sábado/domingo y de cambios de disponibilidad cuando existan citas futuras.
- **Próximo paso recomendado:** atender incidencias operativas de agenda y avanzar con el Módulo 3.
- **Estado del trabajo:** implementado, validado automáticamente y corroborado visualmente por el usuario.

### 2026-10-14 — Duración configurable de citas

- **Objetivo:** permitir configurar una duración global para cada cita, con 60 minutos como valor inicial.
- **Decisión funcional:** duración en minutos en múltiplos de 15; rango de 15 a 1380 minutos. Las horas configuradas siguen siendo inicios manuales, no se generan automáticamente; deben dejar espacio suficiente entre sí para cubrir la duración. No se permiten citas que terminen al día siguiente.
- **Cambios realizados:** `/admin/availability` permite ajustar la duración; validación de disponibilidad, detección de traslapes, cálculo de horarios libres, reservas públicas, confirmaciones y reprogramaciones usan el valor configurado. Guardar un cambio que invalide una cita futura muestra conflictos y no reemplaza la configuración guardada. La reserva guarda la duración y la hora final calculada.
- **Archivos modificados:** `ClinicAvailabilityService.php`, `Availability.php`, `availability.blade.php`, `HasCitas.php`, `AgendarCita.php`, `AvailabilityTest.php`, `AgendarCitaTest.php`, `AVANCES.md`.
- **Pruebas/comandos y resultado:** 45 pruebas y 150 assertions pasaron en `AvailabilityTest`, `AgendarCitaTest`, `ScheduleTest`, `SolicitudTest` y `HasCitasTest`; Pint, compilación de vistas Blade y `git diff --check` pasaron. Se verificaron reserva pública y reprogramación con duración de 120 minutos, rechazo de incrementos inválidos y bloqueo de cambios incompatibles con citas futuras.
- **Decisiones tomadas (responsable/fecha):** minutos configurables en incrementos de 15, predeterminado de 60; máximo de 1380 minutos para mantener fin el mismo día (usuario/2026-10-14).
- **Pendientes o bloqueos:** ninguno funcional conocido; prueba visual realizada por el usuario.
- **Próximo paso recomendado:** validar visualmente el directorio `/admin/patients` y acordar la política de duplicados del Módulo 3.
- **Estado del trabajo:** implementado, validado automáticamente y corroborado visualmente por el usuario.

### 2026-10-14 — Inicio del Módulo 3: directorio y ficha de paciente

- **Objetivo:** iniciar la consulta unificada de pacientes y expediente maestro, respetando la política de acceso a información clínica.
- **Cambios realizados:** se agregó `/admin/patients`, con búsqueda por nombre completo o parcial, DPI, teléfono (acepta consulta con o sin separadores) y correo; filtro de activos/inactivos/todos, filtros avanzados por campos y por expediente relacionado, ordenamiento y paginación mediante `DataTable` + `Searchable`. Se agregó `Searchable` a `Paciente` y se hizo portable la concatenación de campos de accesores para SQLite/MariaDB. La ficha muestra datos de contacto/demográficos, estado y resumen del expediente maestro, además de historial de citas paginado. Se agregó el permiso `page.view.patients`, una página de menú y acceso de lectura para Sysadmin y Recepcionista.
- **Alcance de esta entrega:** consulta de solo lectura; no se crean ni editan pacientes/expedientes, no se combinan registros y la ficha no expone notas clínicas ni documentos. No se requieren migraciones.
- **Archivos modificados:** `routes/admin.php`, `PacientesAccessSeeder.php`, `CatalogosClinicosSeeder.php`, componentes y vistas `Admin/Patient/Index` y `Admin/Patient/Show`, pruebas de directorio y [PLANIFICACION.md](./PLANIFICACION.md).
- **Pruebas/comandos y resultado:** 8 pruebas y 43 assertions pasaron inicialmente en `PatientDirectoryTest` y `CatalogosClinicosSeederTest`. Tras reportarse un error SQL en MariaDB por la concatenación del nombre completo, se corrigió el separador de argumentos `CONCAT` en `Searchable`; la suite de regresión `PatientDirectoryTest` + `SolicitudTest` pasó con 16 pruebas y 66 assertions, junto con Pint, compilación de vistas Blade y `git diff --check`.
- **Decisiones tomadas (responsable/fecha):** listado/ficha protegidos por `page.view.patients`; acceso de lectura asignado a Recepcionista y Sysadmin (implementación/2026-10-14).
- **Pendientes o bloqueos:** implementar la detección de coincidencias y fusión manual; hoy la landing puede generar un registro de paciente nuevo por solicitud y esta entrega no intenta unir esos registros.
- **Próximo paso recomendado:** sugerir candidatos por DPI/teléfono/correo/nombre en flujos de alta y recepción; permitir selección manual y fusión explícita iniciada por recepción. La fusión trasladará el historial al paciente principal, conservará el origen inactivo y enlazado, pedirá revisión para elegir valores de perfil en conflicto y dejará auditoría de la operación.
- **Estado del trabajo:** primera entrega de consulta implementada, validada automáticamente y corroborada visualmente por el usuario (2026-10-14).

### 2026-10-14 — Alta y edición de pacientes con detección de coincidencias

- **Objetivo:** permitir a recepción crear/actualizar pacientes desde el directorio, evitando duplicados inadvertidos y manteniendo un expediente maestro por paciente.
- **Cambios realizados:** se añadieron los formularios Livewire de alta y edición, los permisos `patients.create` y `patients.edit`, y el servicio `PatientMatchService` para sugerir coincidencias por DPI, teléfono, correo y nombre. Si hay candidatos durante un alta, se requiere confirmación explícita para crear un registro diferente; nunca se fusionan automáticamente. La creación/actualización y el registro de auditoría ocurren en transacción; el alta crea o reutiliza el expediente único.
- **Archivos modificados:** `app/Livewire/Admin/Patient/Index.php`, `app/Services/PatientMatchService.php`, `database/seeders/PacientesAccessSeeder.php`, `resources/views/livewire/admin/patient/index.blade.php`, pruebas de directorio y permisos.
- **Pruebas/comandos y resultado:** `PatientDirectoryTest` y `CatalogosClinicosSeederTest`: 13 pruebas y 65 assertions pasaron; Pint, compilación/limpieza de vistas y `git diff --check` pasaron.
- **Decisiones tomadas (responsable/fecha):** sugerencias, no fusión automática; recepción debe confirmar explícitamente un alta cuando se detectan candidatos (usuario/2026-10-14).
- **Pendientes o bloqueos:** la captura desde el directorio está lista; las solicitudes públicas/administrativas aún pueden generar pacientes separados y no ofrecen selección asistida. La fusión manual sigue pendiente.
- **Próximo paso recomendado:** integrar la revisión de posibles coincidencias en el flujo de recepción de solicitudes para permitir reutilizar un paciente existente sin perder los datos enviados en la solicitud; luego abordar la fusión manual con traslado de relaciones, resolución de conflictos y auditoría.
- **Estado del trabajo:** implementado, validado automáticamente y corroborado visualmente por el usuario en `/admin/patients` (2026-10-14).

### 2026-10-14 — Reutilización de pacientes desde solicitudes

- **Objetivo:** permitir a recepción vincular una solicitud con un expediente existente cuando las coincidencias confirman que se trata de la misma persona, evitando expedientes paralelos.
- **Cambios realizados:** la modal de recepción sugiere coincidencias con `PatientMatchService`. La selección requiere confirmación explícita; `PatientMergeService` bloquea y revalida pacientes y solicitud dentro de una transacción, traslada las relaciones con `expediente_id` y los datos de facturación, completa solo campos vacíos del paciente principal, conserva conflictos en el perfil de origen/auditoría e inactiva y enlaza el origen. La ficha del origen señala el expediente vigente.
- **Archivos modificados:** componente y vista de solicitudes, `PatientMergeService`, `PatientMatchService`, modelo `Paciente`, ficha Livewire/vista del paciente, migración del vínculo autorreferencial y pruebas de solicitudes.
- **Pruebas/comandos y resultado:** `SolicitudTest`, `PatientDirectoryTest` y `CatalogosClinicosSeederTest`: 26 pruebas y 130 assertions pasaron; Pint, compilación/limpieza de vistas y `git diff --check` pasaron.
- **Base local:** se detectó que la migración `2026_10_14_131300_add_merged_into_patient_to_pacientes_table` seguía pendiente; se aplicó únicamente esa migración en la base MariaDB local y se confirmó su estado `Ran`.
- **Decisiones tomadas (responsable/fecha):** al reutilizar, llenar únicamente campos vacíos del paciente existente; los conflictos no se sobrescriben y permanecen en el registro inactivo de origen y en auditoría (usuario/2026-10-14).
- **Pendientes o bloqueos:** falta verificar visualmente el selector y el aviso de vínculo. La fusión general manual se registra en la siguiente entrega.
- **Próximo paso recomendado:** validar visualmente la reutilización desde `/solicitudes` y la fusión manual en `/admin/patients`; después construir pestañas clínicas/documentales con autorización específica.
- **Estado del trabajo:** implementado y validado automáticamente; revisión visual pendiente. La migración ya está aplicada en el entorno local.

### 2026-10-14 — Fusión manual de expedientes desde el directorio

- **Objetivo:** permitir a personal autorizado consolidar dos registros verificados desde `/admin/patients`, revisar diferencias antes de confirmar y conservar la trazabilidad.
- **Cambios realizados:** se añadió `patients.merge`, asignado a Sysadmin y Recepcionista. Cada paciente activo puede iniciar una fusión; recepción busca y elige el paciente principal. La vista muestra conflictos de perfil y resumen del expediente, exige una decisión por cada diferencia y confirmación explícita. `PatientMergeService` revalida la selección y decisiones dentro de una transacción, completa campos vacíos, aplica las elecciones explícitas, traslada citas y demás relaciones de expediente/datos de facturación, re-enlaza pacientes previamente fusionados al principal, conserva el estado del expediente principal, inactiva y enlaza el origen y audita las decisiones y relaciones trasladadas.
- **Archivos modificados:** `PatientMergeService.php`, `Admin/Patient/Index.php`, vista del directorio, `PacientesAccessSeeder.php`, pruebas de directorio y prueba de permisos del seeder.
- **Pruebas/comandos y resultado:** `PatientDirectoryTest`, `SolicitudTest` y `CatalogosClinicosSeederTest`: 28 pruebas y 159 assertions pasaron; Pint, compilación/limpieza de Blade y `git diff --check` pasaron.
- **Base local:** se ejecutó `PacientesAccessSeeder` para crear y otorgar `patients.merge` a Sysadmin y Recepcionista.
- **Decisiones tomadas (responsable/fecha):** recepción elige qué valor mantener en cada campo conflictivo; el expediente principal conserva su estado de operación. La fusión no puede deshacerse desde la interfaz (usuario/2026-10-14).
- **Pendientes o bloqueos:** no quedan bloqueos conocidos en esta entrega.
- **Próximo paso recomendado:** agregar las secciones clínicas/documentales protegidas por permisos.
- **Estado del trabajo:** implementado, validado automáticamente y corroborado visualmente por el usuario.

### 2026-10-14 — Secciones protegidas de la ficha del paciente

- **Objetivo:** mostrar información clínica, documental y financiera solo a usuarios autorizados, manteniendo disponibles los datos básicos y citas a quienes ya tienen acceso a pacientes.
- **Cambios realizados:** se añadieron secciones de consultas/planes, archivos/consentimientos y pagos/paquetes en la ficha. Se restringieron también la clasificación, fase y objetivo clínico del resumen. `PacientesAccessSeeder` crea `patients.clinical.view`, `patients.documents.view` y `patients.billing.view`, pero no los concede a roles por nombre; Sysadmin puede asignarlos mediante Spatie. Las consultas de datos de cada sección solo se ejecutan con el permiso. Los listados de archivos y consentimientos no exponen rutas de almacenamiento, firmas, huellas, IP ni datos de dispositivos.
- **Archivos modificados:** componente/vista de ficha, seeder de permisos, pruebas del directorio y pruebas del seeder; esta bitácora y la planificación.
- **Pruebas/comandos y resultado:** `php artisan test tests/Feature/Livewire/PatientDirectoryTest.php tests/Feature/Database/CatalogosClinicosSeederTest.php`: 16 pruebas y 118 assertions pasaron; `vendor/bin/pint --test ...`, `php artisan view:cache` y `git diff --check` pasaron.
- **Base local:** ejecutado `php artisan db:seed --class=Database\Seeders\PacientesAccessSeeder`; los permisos de secciones quedan disponibles para asignación administrativa y no se otorgan automáticamente a roles nominados.
- **Decisiones tomadas:** permisos por sección independientes y asignación controlada por administración, sin roles fijos (usuario/2026-10-14).
- **Pendientes o bloqueos:** ninguno en esta entrega.
- **Próximo paso recomendado:** implementar el formulario de anamnesis general y acordar reglas para el cierre clínico.
- **Estado del trabajo:** implementado, validado automáticamente y corroborado visualmente por el usuario.

### 2026-10-14 — Apertura inicial de consultas clínicas

- **Objetivo:** iniciar el Módulo 4 con creación controlada de una consulta desde una cita confirmada del paciente.
- **Cambios realizados:** la ficha ofrece «Iniciar consulta» a usuarios con `consultations.create`. `ConsultationService` revalida estado y relación paciente/expediente dentro de una transacción, exige sede, profesional y especialidad, impide duplicar consultas para una cita y crea una consulta `abierta` enlazada a cita/expediente. El bloqueo del expediente serializa la asignación de `numero_consulta` por expediente/especialidad. La cita permanece sin cambios y la creación se audita. El permiso se crea con el seeder sin asignación automática a roles.
- **Archivos modificados:** servicio de consultas, componente/vista de ficha, seeder, pruebas de consultas y permisos, bitácora y planificación.
- **Pruebas/comandos y resultado:** `php artisan test tests/Feature/Livewire/ConsultationTest.php tests/Feature/Livewire/PatientDirectoryTest.php tests/Feature/Database/CatalogosClinicosSeederTest.php`: 20 pruebas y 134 assertions pasaron; Pint y `git diff --check` pasaron.
- **Base local:** ejecutado `PacientesAccessSeeder` para registrar `consultations.create`, sin asignarlo automáticamente a roles.
- **Decisiones tomadas:** en esta etapa solo se abre desde citas confirmadas; no se inicia si falta sede, profesional o especialidad. No se alteran estados de cita y aún no se implementan edición ni cierre clínico.
- **Pendientes o bloqueos:** definir datos clínicos obligatorios y quién puede editar/cerrar/reabrir; falta formulario de anamnesis.
- **Próximo paso recomendado:** implementar guardado incremental de anamnesis general y acordar validaciones para cierre.
- **Estado del trabajo:** implementado y validado automáticamente; la comprobación visual quedó bloqueada por falta de flujo para asignar profesional/especialidad, resuelta en la siguiente entrega.

### 2026-10-14 — Asignación de profesional y especialidad a citas

- **Objetivo:** permitir configurar todos los datos requeridos para abrir una consulta, al confirmar citas nuevas y también corregir citas confirmadas que quedaron sin asignación completa.
- **Cambios realizados:** los dos modales de confirmación presentan selectores dependientes de sede, profesional y especialidad. Las especialidades se limitan a las activas asociadas al profesional elegido. Agenda muestra «Completar asignación» en una cita confirmada incompleta; se guardan los recursos sin cambiar el estado y se auditan los valores anteriores/nuevos. El servicio revalida relaciones y traslapes dentro de la transacción.
- **Archivos modificados:** `AppointmentManagementService`, componentes y vistas de agenda/solicitudes, pruebas de ambos flujos y bitácora.
- **Pruebas/comandos y resultado:** `php artisan test tests/Feature/Livewire/ScheduleTest.php tests/Feature/Livewire/SolicitudTest.php tests/Feature/Livewire/ConsultationTest.php`: 31 pruebas y 135 assertions pasaron; Pint, `php artisan view:cache` y `git diff --check` pasaron.
- **Decisiones tomadas:** asignar especialidad al confirmar junto a sede/profesional; usuarios no pueden escoger una especialidad ajena al profesional.
- **Pendientes o bloqueos:** comprobar visualmente «Completar asignación» en agenda y después iniciar consulta desde esa cita.
- **Próximo paso recomendado:** completar la verificación visual, luego implementar anamnesis general.
- **Estado del trabajo:** implementado y validado automáticamente; verificación visual pendiente.

### 2026-10-14 — Corrección de asignación con opciones únicas

- **Objetivo:** permitir guardar la asignación cuando los selectores de sede, profesional o especialidad tienen una sola opción disponible.
- **Cambios realizados:** los selectores de especialidad ahora sincronizan inmediatamente con Livewire. Al seleccionar una sede o profesional, el componente autoelige el profesional/especialidad cuando solo existe una opción; al abrir el modal, también autocompleta opciones únicas desde el inicio. Se aplica a los flujos de Agenda y Solicitudes.
- **Archivos modificados:** componentes y vistas de Agenda/Solicitudes, prueba de asignación en `ScheduleTest` y esta bitácora.
- **Pruebas/comandos y resultado:** `php artisan test tests/Feature/Livewire/ScheduleTest.php tests/Feature/Livewire/SolicitudTest.php`: 28 pruebas y 123 assertions pasaron; Pint y `php artisan view:cache` pasaron.
- **Decisiones tomadas:** una opción única se considera una selección inequívoca y se asigna automáticamente; con varias opciones, el usuario debe elegir.
- **Pendientes o bloqueos:** volver a comprobar en navegador que el botón queda habilitado y que la consulta puede iniciarse.
- **Próximo paso recomendado:** reintentar «Completar asignación» y «Iniciar consulta»; después continuar con anamnesis general.
- **Estado del trabajo:** corregido y validado automáticamente; verificación visual pendiente.

### 2026-10-14 — Captura de anamnesis general

- **Objetivo:** documentar la evaluación inicial de una consulta abierta con acceso clínico autorizado.
- **Cambios realizados:** nueva pantalla de anamnesis enlazada desde la ficha del paciente; permite crear y actualizar motivo, historia, antecedentes, alergias, medicamentos, hábitos, signos vitales, medidas y observaciones. Solo `consultations.edit` puede acceder/guardar; el servicio revalida el estado bajo transacción y audita los nombres de campos modificados sin copiar datos clínicos a la auditoría. Consultas cerradas no son editables. El IMC permanece como columna generada; se ajustó la expresión para evitar división entera en SQLite y obtener el cálculo correcto.
- **Archivos modificados:** componente/vista de consulta, servicio, ruta, permiso `consultations.edit`, enlace de ficha, migración de anamnesis y pruebas.
- **Pruebas/comandos y resultado:** `php artisan test tests/Feature/Livewire/ConsultationTest.php tests/Feature/Database/CatalogosClinicosSeederTest.php`: 10 pruebas y 61 assertions pasaron; Pint, `php artisan view:cache`, registro de ruta y `git diff --check` pasaron.
- **Decisiones tomadas:** antecedentes patológicos y hábitos se capturan como líneas y se guardan como arrays JSON; estados permitidos para guardar: `abierta` y `en_proceso`. No se automatizó el otorgamiento del permiso a roles.
- **Pendientes o bloqueos:** definir los requisitos y autorizaciones de cierre clínico antes de implementar transiciones de estado.
- **Próximo paso recomendado:** acordar qué datos son obligatorios, quién puede cerrar/reabrir y qué estados debe recorrer la consulta.
- **Estado del trabajo:** implementado, validado automáticamente y corroborado visualmente por el usuario.

### 2026-10-14 — Verificación visual de anamnesis general

- **Resultado:** el usuario confirmó que puede visualizar la anamnesis, guardar datos y volver a consultarlos o editarlos.
- **Pendientes:** acordar las reglas clínicas y autorizaciones para cierre/reapertura antes de implementar ese flujo.
- **Estado del trabajo:** verificado visualmente por el usuario.

### 2026-10-14 — Cierre y reapertura de consultas

- **Objetivo:** completar las transiciones clínicas acordadas para consultas abiertas.
- **Cambios realizados:** se puede cerrar una consulta en estado `abierta` o `en_proceso` únicamente con motivo y observaciones finales; la operación guarda la anamnesis pendiente y cambia el estado en una misma transacción. Reabrir una consulta `cerrada` la devuelve a `en_proceso`. Cancelación no se mezcla con estas acciones. Se agregaron permisos independientes `consultations.close` y `consultations.reopen`; se puede acceder a la pantalla con cualquiera de los permisos clínicos de edición/cierre/reapertura. Las transiciones se auditan sin incluir contenido clínico. Por preferencia del usuario, las confirmaciones de cierre y reapertura se muestran en modales de Flux en lugar del diálogo nativo del navegador.
- **Pruebas/comandos y resultado:** tras el cambio de modales, `php artisan test tests/Feature/Livewire/ConsultationTest.php`: 11 pruebas y 65 assertions pasaron; Pint, compilación de vistas y `git diff --check` pasaron.
- **Decisiones tomadas (usuario/2026-10-14):** cierre exige motivo y observaciones finales; permisos separados para cerrar/reabrir; se permite cerrar desde `abierta` o `en_proceso`, reabrir como `en_proceso`; cancelación queda fuera de este flujo.
- **Pendientes o bloqueos:** otorgar explícitamente los permisos de cierre/reapertura a los usuarios correspondientes y validar visualmente ambas acciones y sus modales.
- **Próximo paso recomendado:** ejecutar el seeder de permisos en la base local, asignar los permisos deseados desde administración y probar cierre/reapertura en navegador.
- **Estado del trabajo:** implementado y validado automáticamente; verificación visual pendiente.

### 2026-10-14 — Formularios de anamnesis por especialidad

- **Objetivo:** capturar los datos clínicos adicionales ya contemplados en el esquema, sin mezclar formularios de terapias distintas.
- **Cambios realizados:** se añadieron paneles independientes para evaluación homeopática, quiropráctica y de acupuntura, disponibles para cualquier consulta/paciente sin depender de la especialidad asignada a la cita. Cada formulario se persiste solo cuando tiene datos y se vuelve a cargar para edición; la especialidad de la consulta sigue siendo el contexto del profesional/terapia, no un filtro de evaluación. Quiropraxia valida enums, semanas de gestación y booleanos; Acupuntura captura los campos JSON como listas (una opción por línea) y valida humedad. El servicio valida campos por formulario y registra auditoría de campos cambiados sin valores clínicos. Se ajustó el modelo para las tablas de Quiropraxia y Acupuntura, que no tienen columnas de timestamps.
- **Archivos modificados:** componente y servicio de consulta; modelos de Quiropraxia/Acupuntura; vista principal y tres parciales; pruebas.
- **Pruebas/comandos y resultado:** `php artisan test tests/Feature/Livewire/ConsultationTest.php tests/Feature/Database/CatalogosClinicosSeederTest.php`: 16 pruebas y 119 assertions pasaron; Pint, `php artisan view:cache` y `git diff --check` pasaron. Pruebas de una consulta Homeopatía verifican que los tres formularios se muestran, guardan y vuelven a cargar; también prueban Quiropraxia y Acupuntura desde consultas de otra especialidad.
- **Pendientes o bloqueos:** validar visualmente los formularios; definir opciones clínicas controladas para campos JSON libres de Acupuntura si el equipo clínico las requiere.
- **Próximo paso recomendado:** continuar con evaluación integral MBI, informe por sistemas y test de intoxicación en componentes separados.
- **Estado del trabajo:** implementado y validado automáticamente; verificación visual pendiente.

### 2026-10-14 — Formularios clínicos independientes y cierre explícito

- **Objetivo:** mostrar las anamnesis por separado, permitir guardar cada una sin afectar las demás y separar el cierre de consulta de la captura clínica.
- **Cambios realizados:** la pantalla de consulta muestra tarjetas de anamnesis general, Homeopatía, Quiropraxia y Acupuntura con estado guardado/pendiente. Cada tarjeta abre únicamente su formulario, y cada acción persiste solo ese formulario. Una evaluación especializada puede crear el registro padre sin motivo general previo. El modal Flux de cierre muestra el estado de los cuatro formularios, solicita motivo y observaciones finales en propiedades independientes, y no guarda borradores clínicos no persistidos.
- **Archivos modificados:** `app/Livewire/Admin/Consultation/Show.php`, `app/Services/ConsultationService.php`, `resources/views/livewire/admin/consultation/show.blade.php`, `tests/Feature/Livewire/ConsultationTest.php`, migración nullable para el motivo y este registro/plan.
- **Pruebas/comandos y resultado:** `php -d extension=sqlite3 -d extension=pdo_sqlite vendor\bin\phpunit tests\Feature\Livewire\ConsultationTest.php`: 14 pruebas y 105 assertions pasaron, incluida la nueva migración con SQLite. Pint en los tres archivos PHP modificados, `php artisan view:cache` y `git diff --check` pasaron.
- **Decisiones tomadas (usuario/2026-10-14):** cada anamnesis se abre y guarda por separado; el cierre continúa como acción explícita con resumen y datos de cierre propios.
- **Pendientes o bloqueos:** revisar visualmente las tarjetas y el modal Flux; el comando `php artisan test` del entorno no carga la extensión SQLite de PHP, por lo que se ejecutó PHPUnit directamente con las extensiones habilitadas.
- **Próximo paso recomendado:** ejecutar pruebas dirigidas; luego el usuario puede validar visualmente el flujo y continuar con MBI, informe por sistemas y test de intoxicación.
- **Estado del trabajo:** implementado y validado automáticamente; verificación visual pendiente.

### 2026-10-23 — Autenticación y suite completa con PHP 8.5

- **Objetivo:** alinear el módulo de autenticación heredado y ejecutar la suite solicitada con la versión activa de PHP.
- **Cambios realizados:** Fortify y los tests de autenticación apuntan a `App\Models\Admin\User`; se corrigió la referencia de la factory, se incorporó `name` nullable a la migración consolidada de `users` y se ajustó el accessor `name` para usar la información del perfil cuando falta el valor. La eliminación de cuenta ahora respeta el borrado lógico. Los flags de capacidades de seguridad se exponen a Livewire, y las pruebas comparan etiquetas traducidas y consideran el soft delete. Los helpers de fechas de prueba ahora reasignan el valor devuelto al incrementar, compatible con Carbon immutable en PHP 8.5.
- **Pruebas y validaciones:** auth/ajustes/dashboard: 31 pruebas y 79 assertions pasaron. Suite completa con PHP 8.5.11: 122 pruebas y 531 assertions pasaron en 23.2 segundos. La prueba aislada de reserva que antes se quedaba ejecutando ahora pasa en menos de un segundo. PHP lint y Pint dirigidos pasan; `git diff --check` pasa.
- **Pendiente:** PHPStan global conserva diagnósticos ya existentes; quedan fuera de este arreglo.
- **Estado del trabajo:** implementado y verificado con suite completa.

### 2026-10-23 — Evaluador MBI por sede

- **Objetivo:** dirigir las primeras visitas reservadas desde la landing a un profesional real habilitado para evaluación MBI en la sede seleccionada.
- **Cambios realizados:** se añadió el mapeo único de evaluador por sede, una pantalla de configuración en Disponibilidad, la especialidad Evaluación MBI y el seeder demo local. Solicitudes y Agenda muestran y seleccionan el evaluador configurado para las citas `primera_vez`; el servicio valida sede, profesional y especialidad para confirmaciones y asignaciones incompletas, aunque se manipule el estado del formulario. Las citas de seguimiento conservan el flujo de asignación normal.
- **Archivos modificados:** modelo/migración de asignación por sede; relaciones de Sucursal; Availability y su vista; Solicitud y Schedule con sus vistas; servicio de gestión de citas; seeders y pruebas.
- **Pruebas y validaciones:** suite completa con PHP 8.5.11: 125 pruebas y 541 assertions. La migración incremental y `CatalogosClinicosSeeder` se aplicaron correctamente en el entorno local sin reinicializar la base.
- **Pendientes:** validación visual de la pantalla de configuración y de los modales de confirmación; en nuevas sedes, primero debe existir un profesional activo asociado a la sede y con la especialidad MBI.
- **Estado del trabajo:** implementado y verificado automáticamente; revisión visual pendiente.

### 2026-10-22 — Landing, anamnesis integral y reprogramación

- **Objetivo:** ejecutar el plan MARVAS para reserva pública de primera vez, formularios clínicos, reprogramación, migraciones y documentación.
- **Cambios realizados:** la landing fuerza `primera_vez` sin selector; Solicitudes y Agenda muestran selector de fecha y horarios libres al reprogramar, excluyen la propia cita de los traslapes y solo ofrecen inicios futuros (incluida la hora actual si aún no comienza). Se integró en un formulario la anamnesis general, homeopática y quiropráctica/neuromusculoesquelética (incisos 01–06 de `recursos IA/anamnesis.xlsx`); Acupuntura sigue individual e incorpora los campos de `NUEVO.xlsx`. El inciso 07 se muestra en la ficha del paciente. No se cambió el cierre de consulta y el seguimiento se mantiene como cita agendada por personal. Se consolidaron las migraciones incrementales en `create_*`.
- **Migración y seed:** se verificó `APP_ENV=local`, `DB_CONNECTION=mariadb` y `DB_HOST=127.0.0.1`; se ejecutó `php artisan migrate:fresh --seed` y todas las migraciones/seeders finalizaron correctamente. Esta operación reconstruyó la base de datos local autorizada.
- **Pruebas y validaciones:** las pruebas enfocadas de Landing, Solicitudes, Agenda, Consulta y `HasCitas` pasaron: 55 tests, 275 assertions. PHP lint y Pint en los PHP modificados, `php artisan view:cache` y `git diff --check` pasaron. La compilación frontend estaba generada y disponible.
- **Suite completa:** 122 pruebas y 531 assertions pasan con PHP 8.5.11. PHPStan global, incluso con 512 MB, reporta numerosos diagnósticos del proyecto. Pint para los archivos modificados pasa; la comprobación global de Pint reporta archivos previos fuera de este cambio.
- **Pendiente:** revisar visualmente los formularios y la reprogramación en navegador. La carpeta de recursos IA proporcionada por el usuario quedó sin modificar.
- **Estado del trabajo:** alcance implementado; posteriormente, la suite completa pasó con PHP 8.5.11 (122 pruebas, 531 assertions; ver entrada del 2026-10-23). Validación visual pendiente.

### 2026-10-24 — Captura de evaluación integral MBI

- **Agente/persona:** Copilot.
- **Objetivo:** implementar la evaluación MBI prevista en el Módulo 4 usando el esquema existente.
- **Cambios realizados:** se añadió una sección independiente en la pantalla de consulta para crear/editar la evaluación MBI. Captura integración clínica, sistemas comprometidos, hallazgos, objetivo terapéutico, observaciones, especialidad principal y complementaria y selección Modelo 6+. Las listas se introducen por líneas y se guardan como JSON; no se inventaron catálogos de Modelo 6+, que quedan pendientes de definición clínica. El guardado bloquea la consulta dentro de una transacción, solo permite estados abierta/en proceso y audita nombres de campos sin incluir valores clínicos. La ficha del paciente muestra el resumen MBI bajo el permiso clínico existente.
- **Archivos modificados:** `app/Livewire/Admin/Consultation/Show.php`, `app/Services/ConsultationService.php`, `app/Livewire/Admin/Patient/Show.php`, `resources/views/livewire/admin/consultation/show.blade.php`, `resources/views/livewire/admin/consultation/assessments/mbi.blade.php`, `resources/views/livewire/admin/patient/show.blade.php`, `tests/Feature/Livewire/ConsultationTest.php`, `PLANIFICACION.md` y `AVANCES.md`.
- **Pruebas/comandos y resultado:** `php -d extension=sqlite3 -d extension=pdo_sqlite vendor\bin\phpunit tests\Feature\Livewire\ConsultationTest.php`: 17 pruebas y 144 assertions pasaron. También pasaron Pint en los PHP modificados, `php artisan view:cache`, PHP lint y `git diff --check`. `php artisan test` no pudo arrancar por falta del controlador PDO SQLite en la configuración CLI normal; PHPUnit se ejecutó con las extensiones disponibles habilitadas para ese proceso.
- **Decisiones tomadas (responsable/fecha):** mantener temporalmente como texto libre las listas de especialidad complementaria y selección Modelo 6+ hasta acordar catálogos clínicos (implementación/2026-10-24).
- **Pendientes o bloqueos:** definir catálogos del Modelo 6+ y completar informe por sistemas, test de intoxicación y mapas corporales.
- **Próximo paso recomendado:** implementar el informe por sistemas como componente de consulta independiente.
- **Estado del trabajo:** implementado y validado automáticamente; la revisión visual de la anamnesis integral ya fue confirmada por el usuario.

### 2026-10-24 — Catálogos administrativos y creación de cita interna

- **Agente/persona:** Copilot.
- **Objetivo:** entregar administración de sedes, especialidades y profesionales, además de un punto autenticado de creación de citas concertadas por recepción.
- **Cambios realizados:** se añadieron páginas CRUD Livewire para sedes/especialidades con activación/desactivación y especialidad con `slug` único; profesionales vinculan cuentas existentes sin crear usuarios ni modificar roles, con asignaciones principales a sedes y especialidades. Se añadieron páginas/rutas y permisos restringidos a Sysadmin. La Agenda incluye creación directa para pacientes activos con expediente; una transacción revalida sede, profesional, especialidad, primera visita MBI, disponibilidad global, duración y traslape, registra el usuario y audita la cita, que se crea `confirmada`. Recepción y Sysadmin reciben `appointments.create`.
- **Archivos modificados:** `app/Livewire/Admin/Clinical/Branches.php`, `Specialties.php`, `Professionals.php`, `app/Livewire/Admin/Schedule.php`, `app/Models/Profesional.php`, `app/Services/AppointmentManagementService.php`, vistas Livewire de catálogos y agenda, `routes/admin.php`, `database/seeders/ClinicalCatalogAccessSeeder.php`, `AgendaAccessSeeder.php`, `CatalogosClinicosSeeder.php`, `tests/Feature/Livewire/ClinicalCatalogsTest.php`, `tests/Feature/Database/CatalogosClinicosSeederTest.php`, `PLANIFICACION.md` y `AVANCES.md`.
- **Pruebas/comandos y resultado:** `php -d extension=sqlite3 -d extension=pdo_sqlite vendor\bin\phpunit tests\Feature\Livewire\ClinicalCatalogsTest.php tests\Feature\Livewire\ScheduleTest.php tests\Feature\Database\CatalogosClinicosSeederTest.php`: 27 pruebas y 112 assertions pasaron. Pint dirigido, PHP lint, `php artisan view:cache` y `git diff --check` pasaron.
- **Decisiones tomadas (responsable/fecha):** Sysadmin administra cuentas, roles y permisos y vincula la cuenta al profesional; las citas concertadas directamente por Recepción se guardan como `confirmada` (usuario/2026-10-24).
- **Pendientes o bloqueos:** realizar verificación visual de los formularios con perfiles reales.
- **Próximo paso recomendado:** cerrar pruebas dirigidas, verificar UX/permisos en navegador y luego implementar informe por sistemas, test de intoxicación y mapas corporales.
- **Estado del trabajo:** completo y validado automáticamente; revisión visual pendiente.

### 2026-10-24 — CRUD de catálogos al patrón DataTable/Searchable

- **Agente/persona:** Copilot.
- **Objetivo:** alinear sedes, especialidades y profesionales con la forma de trabajo de `Admin/Pages`.
- **Cambios realizados:** los tres componentes usan el trait `DataTable` (`$headers`, `rows()` computada con `filterAdvance`, orden inicial por nombre) y sus vistas usan `<x-data-table>` con `@interact` (estado y menú de acciones). `Sucursal`, `Especialidad` y `Profesional` usan `Searchable`; búsqueda por nombre/slug/dirección/cédula/correo de la cuenta. Las columnas de sedes y especialidades del profesional se excluyen de búsqueda/orden. Se conserva activar/desactivar (sin soft delete ni borrado físico).
- **Archivos modificados:** componentes y vistas en `Admin/Clinical`, los tres modelos, `tests/Feature/Livewire/ClinicalCatalogsTest.php`, `PLANIFICACION.md` y `AVANCES.md`.
- **Pruebas/comandos y resultado:** ClinicalCatalogsTest + ScheduleTest + CatalogosClinicosSeederTest: 27 pruebas, 117 assertions pasaron; Pint y `view:cache` OK.
- **Decisiones tomadas (responsable/fecha):** seguir el patrón de `Pages` (usuario/2026-10-24).
- **Pendientes o bloqueos:** verificación visual en navegador.
- **Próximo paso recomendado:** informe por sistemas, test de intoxicación y mapas corporales.
- **Estado del trabajo:** completo y validado automáticamente.

### 2026-10-24 — Convenciones de CRUD y permisos en el plan

- **Agente/persona:** Copilot.
- **Objetivo:** dejar por escrito reglas para futuras IA.
- **Cambios realizados:** se añadieron a `PLANIFICACION.md` (secciones 2 y 6) la regla de CRUD (`Searchable` + `<x-data-table>` + `@interact`, estilo `Pages`) y la regla de permisos. Se creó `database/seeders/PermissionsAccessSeeder.php` (idempotente, constantes `PAGE_PERMISSIONS`, `ACTION_PERMISSIONS`, `ROLE_PERMISSIONS`; Sysadmin recibe todo) y se registra al final de `DatabaseSeeder`. Los permisos anteriores se quedan en sus seeders.
- **Decisiones tomadas (responsable/fecha):** usuario/2026-10-24.
- **Estado del trabajo:** completo.

### 2026-10-24 — Permisos compilados en PermissionsAccessSeeder

- **Agente/persona:** Copilot.
- **Cambios realizados:** los 47 permisos (11 de página y 36 de acción) y la asignación al rol Recepcionista pasaron de `AdminAccessSeeder`, `AgendaAccessSeeder`, `SolicitudesAccessSeeder`, `PacientesAccessSeeder` y `ClinicalCatalogAccessSeeder` a `PermissionsAccessSeeder`, que además da todo a Sysadmin si el rol existe. Esos seeders llaman al central (excepto Admin, que lo llama `DatabaseSeeder` antes de crear el rol Sysadmin) y solo registran páginas del menú. `AdminAccessSeederTest` se ajustó.
- **Pruebas:** suite completa 135 pruebas, 595 assertions OK; Pint OK.
- **Estado del trabajo:** completo.

## Plantilla para registrar una entrega para registrar una entrega

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
