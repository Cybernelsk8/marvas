# Avances de desarrollo de MARVAS

> Bitácora compartida de estado. Leer este archivo junto con [PLANIFICACION.md](./PLANIFICACION.md) al retomar el trabajo. Actualizarlo al comenzar y al terminar cada tarea de un agente.

## Estado general

- **Última actualización:** 2026-10-13.
- **Fase sugerida:** Fase A — cerrar el ciclo mínimo de solicitud y confirmación de cita.
- **Resumen:** ya existe landing pública, componente Livewire para solicitar cita, bandeja inicial de solicitudes y esquema de dominio amplio. La bandeja de recepción está incompleta y los módulos de expediente/consulta/planes/pagos/inventario aún no aparecen como interfaces Livewire en el inventario revisado.
- **Importante:** este resumen refleja los archivos observados en el árbol de trabajo al crear la bitácora, no implica que los cambios estén confirmados en Git ni que la aplicación esté desplegada.

> **Reutilización transversal:** consultar la sección “Componentes, traits, servicios y helpers reutilizables” de `PLANIFICACION.md` antes de construir tablas, calendarios, selectores, gráficos, utilidades o lógica común. En especial, estudiar el contrato de `x-data-table` + `DataTable` + `Searchable` + `HeaderMap` y los ejemplos `@interact` de Admin.

## Mapa por módulo

| Módulo | Estado | Evidencia / situación actual | Próximo resultado |
|---|---|---|---|
| 0. Fundamentos y permisos | Parcial | Hay autenticación, permisos Spatie, áreas, roles, páginas y permisos; existen rutas admin con middleware. Falta definir permisos de cada módulo clínico/operativo. | Aprobar matriz de roles/permisos para recepción, profesionales, caja, inventario y administración. |
| 1. Solicitudes y recepción | Parcial | `App\Livewire\Solicitud` y vista `resources/views/livewire/solicitud.blade.php` listan citas y abren modal. `store()` está vacío; confirmar, reprogramar y generar enlace no están conectados. | Alinear estados y hacer operativas edición, contacto y confirmación segura. |
| 2. Agenda y disponibilidad | Parcial | `AgendarCita`, `HasCitas`, componente calendario y prueba unitaria de horarios existen. La disponibilidad es global; falta agenda interna y coordinación por profesional/sucursal. | Definir alcance de recursos y fuente de horarios; centralizar validación de conflictos. |
| 3. Pacientes y expediente | Parcial en datos / pendiente en pantallas | Modelos y migraciones `Paciente`, `ExpedienteMaestro`, facturación y domicilios existen; no se encontró una pantalla clínica/paciente específica en Livewire. | Crear búsqueda de pacientes y ficha del expediente sin duplicados. |
| 4. Consultas y evaluación | Parcial en datos / pendiente en pantallas | Tablas/modelos de consultas, anamnesis, evaluaciones e informes existen; no se encontró flujo Livewire de consulta. | Diseñar flujo clínico y formulario general por especialidad. |
| 5. Clasificación y tratamiento | Parcial en datos / pendiente en pantallas | Existen clasificación, plan terapéutico, paquete asociado y evolución en esquema/modelos. | Definir Modelo 6+ con el equipo y crear flujo de clasificación/plan/evolución. |
| 6. Consentimientos y archivos | Parcial en datos / pendiente en pantallas | Hay plantillas/registros de consentimiento, archivos y campos de trazabilidad. | Definir política legal/de almacenamiento y crear pantallas seguras. |
| 7. Paquetes, pagos y facturación | Parcial en datos / pendiente en pantallas | Existen paquetes clínicos/paciente, pagos y datos de facturación. | Acordar reglas financieras e implementar catálogo/registro de cobro. |
| 8. Inventario y compras | Parcial en datos / pendiente en pantallas | Existen productos, lotes, stock por sucursal, órdenes y movimientos. | Acordar reglas de existencias y construir recepción de compra con movimientos atómicos. |
| 9. Notificaciones y recordatorios | Parcial en datos / pendiente en pantallas | Hay tabla/modelo de notificaciones, flag de recordatorio en citas e ideas en `IDEAS.txt`; no se encontró envío operativo. | Elegir canal/proveedor y diseñar registro de intentos y seguridad de enlaces. |
| 10. Dashboard/reportes | Pendiente | `resources/views/dashboard.blade.php` mantiene contenido placeholder. | Definir indicadores por perfil y reemplazar placeholders con datos autorizados. |
| 11. Landing y reserva | Parcial | Página principal y páginas de terapias existen; `AgendarCita` persiste paciente, expediente y cita. Revisar estado de la cita, disponibilidad, validación y mensaje de confirmación. | La reserva debe crear una solicitud identificable y recibirse en el módulo 1. |

## Implementado u observado en el repositorio

### Sitio público

- Rutas públicas para inicio, servicios y agendamiento en `routes/landing-page.php` y su registro en `bootstrap/app.php`.
- Vistas de inicio y servicios en `resources/views/landing-page`.
- Formulario Livewire `App\Livewire\LandingPage\AgendarCita`.
- Trait `App\Livewire\Traits\HasCitas` para horarios, eventos, fechas completas y disponibilidad.
- Prueba unitaria existente: `tests/Unit/Livewire/HasCitasTest.php`.

### Solicitudes internas

- Ruta `solicitudes` con autenticación y verificación en `routes/web.php`.
- Componente `App\Livewire\Solicitud` con tabla/tarjetas, filtros, paginación y modal de inspección.
- El modal recoge diversos datos demográficos/contacto de `Paciente`.
- Pendiente de implementar persistencia del modal (`store()` vacío), confirmación y cambio de fecha; la opción de generar enlace es visual.
- Estado por defecto de la consulta inicial: `solicitada`. Verificar compatibilidad con el estado con el que `AgendarCita` crea actualmente la cita.

### Administración y plataforma

- Pantallas Livewire para administración de usuarios, detalle de usuario, áreas, páginas, permisos y roles.
- Ajustes Livewire de perfil, apariencia y seguridad.
- Layout interno con sidebar Flux y notificaciones toast.
- `resources/views/dashboard.blade.php` es una estructura inicial, no un dashboard funcional.

### Dominio de datos

- Migraciones para pacientes, expediente, citas, consultas, anamnesis general y por terapia, evaluación MBI, clasificación, planes, evolución, consentimientos, archivos, pagos, paquetes, inventario, sucursales, profesionales, notificaciones y auditoría.
- Modelos Eloquent cubren muchas relaciones y casts; su existencia no demuestra que las operaciones correspondientes estén implementadas en UI.

## Inconsistencias/pendientes inmediatos

1. **Estado de reserva:** definir y aplicar el recorrido de estado. El plan recomienda crear la cita pública como `solicitada`, confirmar desde recepción y conservar las alternativas de cancelación/reprogramación.
2. **Confirmación de recepción:** agregar validación de transición, autorización y transacción; revisar disponibilidad de nuevo al guardar.
3. **Datos del paciente:** guardar de forma validada los cambios del modal; definir detección manual de posibles pacientes duplicados.
4. **Motivo y datos faltantes:** confirmar campos obligatorios y explicar cuáles faltan, sin eliminar el motivo enviado desde la landing.
5. **Choques de agenda:** la disponibilidad actual no separa profesional/sucursal. Acordar si se conserva agenda global en MVP.
6. **Filtro por tipo:** verificar que el valor interno sea el enum `primera_vez`; la etiqueta legible no debe usarse como valor de consulta.
7. **Enlace de formulario y recordatorios:** están planteados en `IDEAS.txt`, pero no se encontró implementación.

## Pruebas y verificaciones conocidas

| Alcance | Verificación | Resultado conocido |
|---|---|---|
| Disponibilidad de citas | `tests/Unit/Livewire/HasCitasTest.php` | Hay pruebas para eventos, solapamiento de horarios y días completamente ocupados. No se volvió a ejecutar al crear esta documentación. |
| Autenticación/perfil/seguridad | Pruebas en `tests/Feature/Auth` y `tests/Feature/Settings` | Existen pruebas; el estado de ejecución actual no se verificó. |
| Bandeja y formulario de cita | No se encontró prueba Feature específica en el inventario revisado | Pendiente de añadir. |
| Consultas, expediente, pagos e inventario | No se encontró cobertura de sus pantallas porque no se encontraron tales pantallas | Pendiente al implementar los módulos. |

## Decisiones de negocio

| Tema | Decisión | Responsable | Fecha |
|---|---|---|---|
| Estado inicial de cita web y transición a confirmada | Pendiente. Recomendación: `solicitada` al enviar; recepción la confirma después de contactar. | Por definir | — |
| Disponibilidad global o por profesional/sucursal | Pendiente. El trait actual calcula disponibilidad global. | Por definir | — |
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

1. **Decidir estados y disponibilidad de citas.** Acordar si las reservas públicas son solicitudes, si recepción asigna fecha distinta y si el calendario opera globalmente o por recursos.
2. **Implementar Módulo 1 en una rama de trabajo controlada.** Completar guardado de datos, transición de estado, confirmación/cancelación y pruebas Livewire; incluir autorización.
3. **Alinear el flujo público con recepción.** Probar el recorrido completo: formulario válido → cita `solicitada` → aparece en bandeja → edición/contacto → cita confirmada.
4. **Implementar agenda interna.** Reutilizar las mismas reglas de disponibilidad y evitar doble reserva con transacciones.
5. **Después, crear búsqueda/ficha de paciente y comenzar el flujo de consulta clínica.**

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
