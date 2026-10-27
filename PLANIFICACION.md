# Plan maestro de desarrollo de MARVAS

> Documento de planificación funcional y técnica. Describe lo que se propone construir; no implica que esos módulos ya estén implementados. Para el estado real del trabajo, decisiones tomadas y siguiente paso, consultar [AVANCES.md](./AVANCES.md).

## 1. Propósito del producto

MARVAS se plantea como una plataforma para una clínica de medicina biológica e integrativa. Debe enlazar la captación pública de pacientes con el trabajo diario de recepción, la agenda, el expediente clínico, los planes terapéuticos y la operación administrativa de una o varias sucursales.

El sitio público presenta el Modelo 6+ y terapias como homeopatía, naturopatía, quiropraxia, masaje muscular, acupuntura e iridología. El sistema interno debe permitir que el equipo reciba solicitudes de citas, contacte al paciente, complete sus datos, confirme una cita y, posteriormente, registre las evaluaciones y el seguimiento clínico.

### Resultado esperado

1. Una persona puede conocer los servicios y solicitar una cita desde la landing page.
2. Recepción puede revisar cada solicitud, contactar al paciente, completar información faltante, acordar fecha/hora y confirmar o rechazar/cancelar la solicitud.
3. Las citas confirmadas se reflejan en la agenda sin conflictos.
4. El personal autorizado puede mantener un expediente clínico longitudinal, registrar consultas, evaluaciones, terapias, consentimientos, archivos y evolución.
5. La administración puede configurar profesionales, sucursales, paquetes, pagos e inventario, y consultar información operativa.

## 2. Criterios para leer este plan

- **Existente** identifica una ruta, pantalla, componente, modelo o tabla encontrado en el repositorio durante la elaboración de este plan.
- **Parcial** indica que hay una base visible, pero todavía faltan acciones, validaciones o integración para completar el flujo.
- **Propuesto** indica trabajo futuro. No debe marcarse como terminado solo porque ya exista una migración o un modelo.
- Las rutas y nombres de componentes que todavía no existen son propuestas; se deben adaptar a las convenciones reales del proyecto antes de crearlos.
- La implementación actual usa Laravel 13, Livewire y Flux. Para pantallas internas se priorizarán componentes Livewire de página completa bajo `app/Livewire` y sus vistas en `resources/views/livewire`. Las rutas administrativas se agrupan bajo `/admin` con autenticación.
- El proyecto solo tiene `App\Http\Controllers\Controller` como controlador convencional visible en esta revisión. No crear controladores por costumbre: una pantalla interactiva debe seguir el patrón Livewire existente. Usar controladores solo cuando una integración o respuesta HTTP específica lo justifique.
- Las tablas clínicas contienen información sensible. Aplicar autorización en servidor, validación, registro de auditoría cuando corresponda y mínima exposición de datos.
- **Convenciones obligatorias para toda IA/persona:** (1) cada CRUD usa `Searchable`, `<x-data-table>` y `@interact` al estilo de `Livewire/Admin/Pages.php`; (2) todo permiso nuevo (páginas y acciones) se agrega en `database/seeders/PermissionsAccessSeeder.php`; (3) cada método de una clase Livewire se comenta con explicación y ejemplo de uso al estilo del componente `calendar`, además del DocBlock. Detalle en la sección 6.
## 3. Arquitectura funcional observada

### Cadena de datos prevista

`Landing page → Cita/solicitud → Paciente → Expediente maestro → Cita confirmada → Consulta → Anamnesis/evaluación → Clasificación y plan → Evoluciones/sesiones → Pagos/paquetes/consumos`

La base de datos ya contempla en gran parte esta cadena:

- Personas y atención: `pacientes`, `expediente_maestro`, `citas`, `consultas`.
- Equipo y sedes: `profesionales`, `especialidades`, `sucursales`, `profesional_especialidades`, `profesional_sucursales`.
- Historia clínica: `anamnesis_generales`, anamnesis por terapia, `antecedentes_familiares_detalle`, `evaluacion_integral_mbi`, `clasificacion_paciente`, `regiones_corporales`, `mapas_corporales`, `zonas_dolor`. (`informe_sistemas` y `test_intoxicacion` se eliminaron el 2026-10-26: el informe por sistemas es el inciso 04 de la anamnesis integral y el test de intoxicación se descarta.)
- Tratamiento y seguimiento: `planes_tratamiento`, `paquetes_clinicos`, `paquetes_paciente`, `evolucion_clinica`.
- Soporte y administración: consentimientos, archivos, pagos, productos/lotes/stock, órdenes de compra, movimientos, notificaciones y auditoría.

### Estado actual (2026-10-23)

- **Landing y contenidos de servicios — existente:** portada y páginas públicas de terapias en `resources/views/landing-page`.
- **Formulario público — implementado:** `App\Livewire\LandingPage\AgendarCita` siempre registra la cita como `primera_vez`; no ofrece selector de tipo. La disponibilidad compartida solo presenta horas futuras, incluidas las horas de la franja actual cuando su inicio aún no pasó.
- **Solicitudes y agenda — implementadas:** recepción puede reprogramar eligiendo una fecha y una hora libre. La disponibilidad permite la cita que se está reprogramando sin contarla como conflicto; seguimiento continúa siendo una acción agendada por el personal.
- **Evaluación MBI inicial — implementada:** Disponibilidad administra un profesional Evaluador MBI por sede. Las citas `primera_vez` solo se confirman o completan con ese profesional y la especialidad `evaluacion-mbi`, validado también en el servicio; los seguimientos mantienen la asignación normal.
- **Anamnesis — implementada parcialmente:** la consulta ofrece una anamnesis integrada general, homeopática y quiropráctica/neuromusculoesquelética con los incisos 01–06 del recurso `recursos IA/anamnesis.xlsx`; la evaluación de acupuntura del recurso `NUEVO.xlsx` permanece individual. El resultado del evaluador (inciso 07) se presenta en la ficha del paciente. El cierre de consulta conserva su comportamiento acordado.
- **Administración de usuarios y acceso — existente/parcial:** hay pantallas Livewire para usuarios, roles, permisos, áreas y páginas, además de ajustes de cuenta. Revisar su integración con permisos por pantalla al agregar nuevas áreas.
- **Dashboard — esqueleto:** `resources/views/dashboard.blade.php` contiene placeholders y elementos de interfaz de muestra, no un panel clínico operativo.
- **Migraciones — consolidadas y verificadas:** los cambios incrementales de esquema se incorporaron en sus migraciones `create_*`; `migrate:fresh --seed` terminó correctamente en la base MariaDB local el 2026-10-22. Este comando es destructivo y solo debe repetirse en una base local autorizada.
- **Pruebas — suite completa validada:** 125 pruebas y 541 assertions pasan con PHP 8.5.11, incluidas las pruebas de asignación MBI por sede; la nueva migración y el seeder de catálogos se aplicaron en la base local. La validación visual sigue pendiente.

### Componentes, traits, servicios y helpers reutilizables

Antes de crear una vista, widget, filtro, formateador o utilidad nueva, cada agente debe revisar si ya existe una pieza que resuelva la necesidad. Estos archivos son código compartido: se deben reutilizar con sus contratos existentes y no copiar/modificar para un solo módulo sin revisar los usos actuales.

#### Componentes Blade anónimos

| Componente | Archivo | Uso recomendado y contrato observado |
|---|---|---|
| `<x-calendar>` | `resources/views/components/calendar.blade.php` | Calendario responsive que puede enlazar una fecha con `wire:model`; recibe `events`, límites de fecha, fechas/rangos/días deshabilitados y opciones de tamaño. También emite un evento Livewire al elegir un día. Presenta eventos; el componente padre debe decidir y mostrar el panel/modal de detalle. Revisar la cabecera del Blade antes de usar o ampliar su API. Ya se usa en el formulario público y en pantallas de usuario. |
| `<x-chart>` | `resources/views/components/chart.blade.php` | Renderiza ApexCharts desde un array de configuración, sincroniza series/labels/categorías desde Livewire y reacciona al tema claro/oscuro. Puede despachar un evento Livewire para selección de punto. Los formateadores JavaScript personalizados se ejecutan con `new Function()` en el navegador: solo permitir presets conocidos o expresiones controladas por desarrolladores; nunca construirlas con input de usuario. |
| `<x-date-picker>` | `resources/views/components/date-picker.blade.php` | Selector de fecha con modos `single`, `range` y `multiple`, uno o dos meses, formato, límites, fechas/rangos/días deshabilitados, presets y limpieza. Está integrado con el modelo Livewire y calcula el estado visual de error desde la validación. Revisar el formato que devuelve antes de conectar a fechas del dominio/consultas. Ya hay ejemplos en pantallas administrativas y de usuario. |
| `<x-qr>` | `resources/views/components/qr.blade.php` | Presenta un código QR SVG/PNG y opcionalmente permite descargarlo. Recibe contenido, tamaño, nivel de corrección, colores y nombre de archivo; usa `App\Services\Qr`. El QR solo representa el contenido recibido: no convierte por sí solo un enlace en acceso seguro. Generar tokens/enlaces con alcance y expiración adecuados antes de codificar datos sensibles. |
| `<x-select>` | `resources/views/components/select.blade.php` | Selector Alpine/Livewire con búsqueda opcional, selección múltiple, opciones de arrays/objetos, campo de valor/etiqueta, estado inválido y deshabilitado. Está enlazado con `wire:model`; puede sincronizarse con dependencias como departamento/municipio, como se observa en formularios de usuario. Proveer opciones desde consultas autorizadas y validar siempre sus IDs en el servidor. |
| `<x-data-table>` / `<x-data-table.advance-filter>` / `<x-data-table.filter>` | `resources/views/components/data-table/index.blade.php`, `resources/views/components/data-table/advance-filter.blade.php`, `resources/views/components/data-table/filter.blade.php` y `app/View/Components/DataTable.php` | Tabla responsiva con vista móvil y desktop, paginación, estado de carga, búsqueda/filtros avanzados y slots por columna. En formularios existentes se entrega `:headers="$this->headers"` y `:rows="$this->rows"` y se reemplazan celdas mediante `@interact`. Es la opción preferida para listados administrativos u operativos tabulares; no sustituirla por otra tabla ad hoc sin justificarlo. La vista de solicitudes actual usa tarjetas y controles de filtro manuales; si el UX de tarjetas sigue siendo necesario, reutilizar el trait/scope compartidos o evaluar un modo de tabla/tarjetas sin perder filtros ni accesibilidad. |

#### `data-table` y filtrado avanzado: patrón preferido

El filtrado se compone de varias piezas; consultar todas antes de alterar una:

1. **Vista/componente Blade:** `x-data-table` recibe encabezados y filas, dibuja responsive la tabla y acepta slots de columna. `x-data-table.advance-filter` ofrece el editor avanzado; `x-data-table.filter` se usa para filtros predefinidos, como los de solicitudes.
2. **Slot dinámico `@interact`:** la directiva y `@endinteract` se registran en `app/Providers/AppServiceProvider.php`. Se declara dentro de `x-data-table` con el índice del header y la variable de fila, por ejemplo `@interact('actions', $row) ... @endinteract`. Es adecuada para nombres compuestos, badges, iconos, botones y menús de acciones. La tabla relaciona el índice con el slot y pasa la fila (y el índice/loop de fila); conservar el índice exacto declarado en `$headers`.
3. **Componente de captura de slots:** `app/View/Components/DataTable.php` captura los slots de columna y los entrega a `components.data-table.index`.
4. **Estado/UI Livewire:** `app/Traits/DataTable.php` aporta búsqueda, orden, paginación, filtros, selección de filas, serialización en query string y métodos de reset/set. El componente Livewire define `public array $headers`, prepara `rows()` (normalmente método `#[Computed]`) y usa `processFilters()` en la consulta. Revisar si cada método existente es apropiado antes de reimplementar filtros manualmente.
5. **Consulta Eloquent:** `app/Traits/Searchable.php` aporta el scope `filterAdvance($headers, $params)`. Se incluye en el modelo que alimenta el listado; filtra/busca/ordena según los headers y puede resolver campos de relaciones y accessors.
6. **Metadatos de campos:** `app/Support/DataTable/HeaderMap.php` normaliza headers para que tabla y scope compartan tipos, familias, operadores, relaciones, columnas virtuales/excluidas, eager loads y `withCount`.
7. **Configuración/formato:** revisar `config/datatable.php` para tipos, familias, operadores, locales y formatos; `maskFormatVal()` usa esa configuración para mostrar valores.

**Ejemplo de uso encontrado:** `app/Livewire/Admin/User/Index.php` define headers y una consulta paginada con `filterAdvance`, y `resources/views/livewire/admin/user/index.blade.php` renderiza `<x-data-table>` con `@interact` para nombre/avatar, estado y acciones. También revisar los ejemplos en las vistas de `admin/roles`, `admin/permissions`, `admin/pages`, `admin/areas` y el perfil de usuario. En `App\Livewire\Solicitud` y su vista hay filtros, tabla de resultados paginada y una presentación en tarjetas; tomarla como ejemplo de estado/consulta, no como ejemplo de los slots del componente `x-data-table`.

**Reglas para nuevos listados:**

- Definir `$headers` con índices válidos y etiquetas legibles. Declarar `type` cuando el campo no sea string; marcar explícitamente campos virtuales/excluidos según las convenciones de `HeaderMap`.
- Los índices relacionales deben corresponder a relaciones reales (`relacion.campo`); el scope puede preparar eager loads, pero el componente también debe cargar las relaciones necesarias para lo que la vista muestra.
- El listado debe paginar en base de datos, usar `filterAdvance()` con búsqueda/orden/filtros del trait y resetear la página al cambiar condiciones mediante los hooks ya existentes.
- Limitar los campos disponibles a una lista fija y revisada en código. No aceptar nombres de columna, relaciones u operadores arbitrarios desde el cliente; validar además permisos de las acciones por fila.
- `@interact` renderiza contenido Blade personalizado: escapar valores no confiables con `{{ }}`; no imprimir HTML de usuario sin sanitización.
- Mantener consistentes filtro inicial, filtros rápidos y valores reales del enum/columna. La etiqueta visible no es necesariamente el valor almacenado.
- No activar `select_only` sin verificar que todas las columnas/relaciones/accesores que usa el Blade y la paginación están cubiertos.
- Verificar los usos existentes antes de cambiar un trait, componente, configuración o directiva: son dependencias transversales de varios módulos.

#### Traits compartidos

| Trait | Archivo | Responsabilidad y consideraciones |
|---|---|---|
| `DataTable` | `app/Traits/DataTable.php` | Estado, query string, paginación, búsqueda, orden, filtros avanzados y selección usados por `x-data-table`. Seguir el patrón de los componentes Admin antes de añadir estado paralelo. |
| `Searchable` | `app/Traits/Searchable.php` | Scope Eloquent `filterAdvance`; búsquedas en columnas, relaciones y accessors, filtros tipados y ordenamiento. Requiere headers que describan campos permitidos y relaciones existentes. |
| `Interact` | `app/Traits/Interact.php` | Navegación entre pasos y toggles de navegación en componentes Livewire. El componente consumidor debe definir `steps`; validar las reglas por paso sobrescribiendo `validateCurrentStep()`. Actualmente ese método base no implementa validación por sí mismo. Usarlo solo para flujos realmente secuenciales. |
| `Charts` | `app/Traits/Charts.php` | Builders para tipos de gráficos que devuelven el servicio `Chart`; usar junto con `<x-chart>` cuando se necesiten indicadores. Elegir una forma de chart existente antes de construir opciones manualmente. |
| `HasCitas` | `app/Livewire/Traits/HasCitas.php` | Horarios configurados, consulta de citas, eventos de calendario, fechas completas y franjas disponibles. La disponibilidad es global y usa la duración configurada; solo ofrece inicios futuros y permite excluir la cita actual durante una reprogramación. La revalidación/transacción de guardado sigue siendo responsabilidad del flujo que crea/modifica la cita. |

#### Servicios de aplicación existentes

| Servicio | Archivo | Capacidad observada y pautas de reutilización |
|---|---|---|
| `Chart` | `app/Services/Chart.php` | Builder inmutable de opciones ApexCharts, tipos predefinidos, series, labels, colores, opciones, formato y metas. Se puede usar directamente o mediante `App\Traits\Charts`; entregar `build()` al componente `<x-chart>`. Los callbacks JavaScript personalizados deben ser constantes controladas por el desarrollador. |
| `Qr` | `app/Services/Qr.php` | Genera SVG/PNG, bytes o data URI; valida contenido/tamaño/margen y cachea salidas de texto. Lo usa `<x-qr>`. Evitar codificar información clínica o identificadores predecibles cuando un enlace/token limitado sea suficiente. |
| `Token` | `app/Services/Token.php` | Crea y valida hash corto firmado con RSA, versión `v1`, expiración y claves en `storage/keys`; lee configuración `jwt`. Antes de usarlo, confirmar disponibilidad/rotación de claves, duración, propósito y manejo de excepciones. No asumir que es intercambiable con el otro servicio JWT. |
| `JsonWebToken` | `app/Services/JsonWebToken.php` | Crea/verifica JWT RS256 con issuer/audience/expiración y tiene helper específico `verifyQrHash`. Revisar el contrato/configuración de `jwt`, claves y audiencia antes de reutilizarlo. No decodificar un token y confiar en sus claims sin verificar firma, expiración y audiencia. |
| `Captcha` | `app/Services/Captcha.php` | Genera una respuesta de imagen PNG con GD. Revisar el flujo de consumo y las dependencias de extensión GD antes de integrarlo; no confundir generar la imagen con validar la respuesta del usuario. |

#### Helpers globales

Los helpers están en `app/Helpers/global.php` y se cargan por autoload de Composer. Reutilizarlos solo para su propósito concreto; evitar agregar lógica de negocio extensa o nuevas funciones globales cuando un servicio/componente con tipos claros sea más apropiado.

- `getNestedValue($array, $key)`: obtiene una clave anidada usando una ruta separada por puntos.
- `hasChanged($target, $source)`: compara valores/arrays anidados para saber si hay cambios.
- `hashToken()`: factoría global para `App\Services\Jwt\HashToken`; comprobar la clase y contrato disponibles antes de usarla (es distinta de `App\Services\Token` y `JsonWebToken`).
- `captchaGenerate($text)`: delega la generación de imagen en `Captcha`; no implementa validación del CAPTCHA.
- `formatTelefono($value)`: formatea números de ocho dígitos a `####-####`.
- `dateToHour($fechaDestino)`: calcula diferencia de horas respecto a `new DateTime()` del servidor; comprobar zona horaria y si existe un helper/framework más explícito para el caso.
- `maskFormatVal($value, $type, $options)`: formato de valores para tablas, basado en `config/datatable.php`; utilizado por `x-data-table` para campos tipados.

El archivo global también revela que los formatos y locale de `config/datatable.php` son compartidos por la tabla y sus filtros. No cambiar esa configuración sin comprobar el efecto en listados existentes.

#### Cómo investigar cada pieza antes de reutilizarla

1. Leer la implementación actual del componente/trait/servicio/helper y su configuración asociada.
2. Buscar todos sus usos con `rg` y revisar al menos un ejemplo completo de componente Livewire y vista.
3. Confirmar los datos y forma de salida esperados (incluidos eventos Livewire, nombre de propiedad `wire:model`, tipos y errores).
4. Reutilizar sin duplicar; si hace falta ampliar una API, conservar compatibilidad de todos los usos existentes y agregar pruebas dirigidas.
5. Anotar en [AVANCES.md](./AVANCES.md) una extensión transversal para que los demás agentes sepan que deben revisar los impactos.

### Riesgos e inconsistencias a resolver al implementar

1. **Resuelto:** la cita pública se registra como `solicitada`; recepción confirma, reprograma o cancela mediante las acciones administrativas.
2. El formulario público crea un paciente y expediente en cada envío. Antes de confirmar, recepción debe poder detectar posibles duplicados; no fusionar expedientes automáticamente solo por coincidencia de nombre.
3. `HasCitas` calcula disponibilidad global y no separa por profesional ni sucursal. Para conservar el flujo actual, el MVP debe indicar con claridad si la agenda es compartida; la reserva por profesional/sede requiere modelar y filtrar esos recursos.
4. Revisar la consistencia de los valores enum, filtros, rutas y estados antes de añadir pantallas. Por ejemplo, el filtro de tipo de cita debe usar `primera_vez`, no una etiqueta textual como valor de base de datos.
5. El formulario etiqueta el motivo como obligatorio visualmente, pero actualmente la regla del componente permite que sea nulo. Definir si se hará obligatorio; para solicitudes de recepción es razonable mantenerlo opcional y reflejarlo correctamente en la interfaz.
6. Las reglas de solapamiento están documentadas en `Cita::existeTraslape`; cualquier creación, confirmación o cambio de horario debe aplicar la regla dentro de una transacción. La comprobación de disponibilidad del navegador no basta para evitar carreras.
7. Revisar antes de implementar las migraciones del proyecto con MariaDB/MySQL configurado, especialmente columnas generadas, restricciones `CHECK`, `SET`, enums y ciclos de claves foráneas.

## 4. Plan por módulos

### Módulo 0 — Fundamentos compartidos y control de acceso

**Objetivo:** establecer convenciones para rutas, permisos, estados, auditoría, errores y pruebas que todos los módulos reutilicen.

**Base existente:** Laravel 13; Livewire y Flux; rutas administrativas en `routes/admin.php`, protegidas por `auth` y permisos `can:page.view.*`; Spatie Permission y pantallas para roles/permisos/páginas/áreas; tabla `auditoria`.

**Trabajo propuesto:**

1. Confirmar el mapa de roles reales (recepción, profesional, caja, inventario, administración) y los permisos de cada acción.
2. Definir nombres uniformes para permisos, por ejemplo `page.view.appointments`, `appointment.confirm`, `patient.update`, `consultation.create`, `payment.register`.
3. Mantener permisos de lectura separados de acciones que modifican o exportan datos sensibles.
4. Crear/actualizar las páginas y permisos configurables siguiendo el mecanismo existente de `Pages`, `Roles` y `Permissions`.
5. Definir cuándo registrar auditoría para cambios clínicos, confirmaciones/cancelaciones, cobros, inventario y acceso/exportación de información.
6. Asegurar que Livewire autorice cada acción en el servidor; ocultar un botón no es autorización.
7. Establecer formato común para validación, errores visibles, notificaciones Flux y pruebas de componentes.

**Aceptación:** un usuario sin permiso no puede abrir la página ni invocar directamente acciones Livewire; los usuarios autorizados ven únicamente las funciones de su rol.

### Módulo 1 — Solicitudes entrantes y trabajo de recepción (prioridad inicial)

**Objetivo:** convertir la cita solicitada en una cita revisada y confirmada por recepción.

**Base existente:** ruta autenticada `solicitudes`, `App\Livewire\Solicitud`, `resources/views/livewire/solicitud.blade.php`, relaciones `Cita → ExpedienteMaestro → Paciente` y estados definidos en la migración de `citas`. La vista ya presenta tarjetas, filtros y un modal básico.

**Rutas propuestas:** conservar `solicitudes` o normalizar a `admin/solicitudes`; mantener compatibilidad con enlaces existentes. La ruta debe quedar bajo middleware autenticado y permiso específico de lectura/gestión.

**Pantalla de solicitudes:**

- Lista paginada con búsqueda y filtros por estado, tipo, fecha solicitada y rango de fechas.
- Tarjeta/fila con nombre y contacto, fecha/hora pedida, tipo de cita, motivo, fecha de entrada, estado y datos faltantes.
- Acciones condicionadas por estado: abrir/editar datos, marcar contacto realizado, proponer/cambiar fecha con selector de fecha y horas disponibles, confirmar y cancelar.
- Diferenciar visualmente solicitudes nuevas, pendientes de contacto y resueltas.
- Ofrecer navegación a la ficha del paciente/expediente cuando exista.

**Modal o panel de revisión:**

- Mostrar la solicitud completa y el motivo indicado en la landing.
- Permitir completar datos de paciente contemplados en `Paciente`: nacimiento, sexo, estado civil, DPI, teléfono de emergencia, dirección, ocupación y canal de referencia, según necesidad de negocio.
- Permitir corregir nombres/teléfono/correo y documentar resultado del contacto sin sobrescribir notas originales de la solicitud.
- Permitir elegir fecha/hora acordada; mostrar disponibilidad reciente y validar otra vez en el servidor.
- Confirmar con resumen final antes de guardar; almacenar quién atendió y cuándo si se acuerda incorporar los campos/auditoría necesarios.
- En caso de posible duplicado, mostrar candidatos y requerir una decisión explícita de recepción.

**Componentes y datos:**

- Reutilizar `App\Livewire\Solicitud` mientras el componente continúe siendo manejable; dividir en componentes Livewire de búsqueda/edición solo cuando reduzca complejidad y mantenga estados claros.
- Reutilizar `Paciente`, `ExpedienteMaestro`, `Cita`, `HasCitas`, `Cita::existeTraslape()` y `Flux::modal()/toast`.
- No crear tabla `solicitudes` automáticamente: el diseño actual permite representar la solicitud con una fila de `citas` en estado `solicitada`. Crear persistencia adicional solo si se decide conservar separadamente el mensaje original o el historial de contacto/cambios y se justifica con un esquema concreto.
- Evaluar campos o una tabla de historial para conservar comentarios y eventos de contacto (quién llamó, cuándo, resultado, próxima acción). No reutilizar `notas_recepcion` como historial acumulativo sin definir su semántica.

**Pasos de implementación:**

1. Corregir primero el contrato de estados: formulario público crea `solicitada`; definir transiciones permitidas para confirmar, reprogramar y cancelar.
2. Añadir pruebas Livewire para listado, filtros, autorización, edición validada y persistencia transaccional.
3. Completar la lectura y edición de los datos del paciente en el modal; validar unicidad de DPI y normalizar contacto.
4. Diseñar detección de posibles duplicados y la decisión manual de vincular o conservar un paciente nuevo.
5. Añadir el registro de contacto y su historial con persistencia explícita, si se aprueba como parte del alcance.
6. Implementar confirmación y cancelación con transacción, autorización, validación de estado y auditoría.
7. **Implementado:** reprogramar mediante selector de fecha y lista de horas libres; excluir la propia cita del cálculo de traslapes y volver a validar la fecha y la disponibilidad al guardar.
8. Revisar filtros, etiquetas, paginación, carga móvil y estados vacíos.
9. Completar pruebas de regresión de la reserva pública y de esta bandeja.

**Aceptación:** cada solicitud pública aparece en la bandeja con estado correcto; recepción puede completar los datos, registrar el contacto y confirmar una hora válida; no hay confirmaciones dobles ni pérdida del motivo original; errores y conflictos de horario se notifican sin fingir éxito.

### Módulo 2 — Agenda y configuración de disponibilidad

**Objetivo:** ofrecer a recepción y profesionales una agenda coherente con las citas solicitadas, confirmadas, canceladas y reprogramadas.

**Base existente:** componente de cita pública `AgendarCita`, trait `HasCitas`, calendario `resources/views/components/calendar.blade.php`, citas con inicio/fin, estado, profesional, sucursal y especialidad; `Setting` permite valores tipados y hay una migración para `settings`.

**Pantallas propuestas:**

- Calendario de recepción con vistas día/semana/mes, filtros por sede, profesional, especialidad y estado.
- Agenda individual del profesional.
- Configuración de horarios de atención, duración de cita, pausas, días no laborables y anticipación máxima.
- Listado de disponibilidad para escoger o reprogramar una cita.

**Pasos:**

1. **Decisión acordada (2026-10-13):** mantener la disponibilidad pública global para toda la clínica; no separar horarios por profesional ni sucursal en esta fase.
2. Identificar la forma final de almacenar horarios (settings existentes u otra estructura) y validar intervalos, zonas horarias y excepciones.
3. Centralizar las reglas de disponibilidad para que landing, recepción y agenda usen la misma fuente.
4. **Decisión acordada (2026-10-13) y ampliada (2026-10-14):** recepción asigna sede, profesional y especialidad al confirmar en agenda o solicitudes. La especialidad debe estar activa y asociada al profesional seleccionado. La disponibilidad sigue siendo global; antes de asignar profesional se valida traslape para ese profesional. Citas confirmadas existentes con alguno de esos datos vacíos se pueden completar desde la agenda sin alterar su estado.
5. **Implementado:** calendario Flux/Livewire de recepción con vistas día/semana/mes, filtros por estado y sede (incluyendo citas sin sede), y detalle con la sede relacionada. Las sedes inactivas se conservan como opción de filtro para consultar citas históricas.
6. **Implementado:** acciones directas en agenda para confirmar, completar asignaciones incompletas de citas confirmadas, reprogramar y cancelar; `AppointmentManagementService` comparte reglas transaccionales con Solicitudes.
7. Completar las reglas de disponibilidad y probar límites de intervalos, citas de duración distinta, cancelaciones, turnos que cruzan medianoche si se permiten y concurrencia.
8. **Implementado (2026-10-24):** permitir que recepción cree desde la agenda citas para pacientes activos con expediente. La operación asigna tipo, sede, profesional y especialidad, registra al usuario que agenda y crea el estado `confirmada`; primera visita requiere el Evaluador MBI de la sede. El servidor vuelve a validar disponibilidad global, duración, asignaciones y traslapes dentro de una transacción.

**Aceptación:** todos los puntos de entrada aplican la misma regla, muestran el estado actual y no pueden reservar el horario global ni el mismo recurso en horarios traslapados.

### Módulo 3 — Pacientes y expediente maestro

**Objetivo:** mantener una ficha única, protegida y navegable del paciente.

**Base existente:** modelos `Paciente`, `ExpedienteMaestro`, `DatosFacturacion`, domicilios/geografía y relaciones a citas, consultas, planes, paquetes, pagos, consentimientos, notificaciones y archivos.

**Pantallas propuestas:**

- Búsqueda/listado de pacientes con filtros por nombre, DPI, teléfono y estado.
- Ficha de paciente con datos demográficos y contacto.
- Resumen del expediente con estado, objetivo, fase terapéutica y clasificación vigente.
- Pestañas para citas, consultas, documentos, consentimientos, planes y cuenta/pagos según permisos.
- Formularios de alta y edición con historial y validación de duplicados.

**Pasos:**

1. **Decisión acordada (2026-10-14):** sugerir coincidencias por DPI, teléfono, correo y nombre para que recepción elija si reutiliza un paciente; nunca fusionar automáticamente. Debe existir una acción explícita para fusión manual de pacientes/expedientes. La fusión trasladará el historial al paciente principal, conservará el origen inactivo y enlazado para trazabilidad, exigirá revisar datos de perfil en conflicto y registrará auditoría.
2. **Implementado (2026-10-14):** crear la ruta `/admin/patients`, componente Livewire de directorio que reutiliza `DataTable` y `Searchable`, vista con paginación/búsqueda por nombre, DPI, teléfono y correo, filtro por estado y filtros avanzados por el expediente relacionado.
3. **Implementado (2026-10-14):** ficha Livewire con datos básicos y citas paginadas. Clasificación, objetivo y fase del expediente se consideran datos clínicos y solo se muestran con `patients.clinical.view`.
4. **Implementado (2026-10-14):** alta y edición desde el directorio, permisos separados, sugerencias de coincidencias mediante `PatientMatchService` y confirmación explícita antes de crear si hay candidatos. Creación/edición y auditoría son transaccionales; el alta garantiza/reutiliza el expediente único.
5. **Implementado (2026-10-14):** en recepción se sugieren coincidencias y se requiere confirmación explícita. Al reutilizar, una transacción traslada las relaciones e historial del expediente de origen, completa solo campos vacíos del paciente principal y conserva el origen inactivo/enlazado. Los conflictos de perfil no se sobrescriben y se registran para revisión y auditoría.
6. **Implementado (2026-10-14):** fusión manual desde el directorio con permiso `patients.merge`, búsqueda del paciente principal, resumen de historial y resolución obligatoria de cada conflicto de perfil/resumen del expediente antes de confirmar. La operación revalida y transfiere relaciones en una transacción, preserva el estado del principal, inactiva/enlaza el origen y audita la selección de valores y cantidades trasladadas. Los pacientes previamente fusionados con el origen quedan enlazados directamente al principal final.
7. **Implementado (2026-10-14):** secciones clínicas, documentos/consentimientos y pagos/paquetes protegidas por `patients.clinical.view`, `patients.documents.view` y `patients.billing.view`. Los permisos se crean sin asignarlos a roles nominales; Sysadmin puede asignarlos mediante la administración existente. Listados documentales excluyen rutas, firmas, huellas, IP y contenido de notificaciones.
8. **Verificado visualmente por el usuario (2026-10-14):** alta, coincidencias en solicitudes y fusión manual; las nuevas secciones quedan pendientes de validación visual.

**Aceptación:** recepción puede localizar y completar un paciente; el expediente maestro se mantiene único por paciente y el historial asociado no se pierde ni se muestra a usuarios sin los permisos específicos.

### Módulo 4 — Consultas y evaluación clínica

**Objetivo:** documentar cada encuentro clínico, la evaluación integral y los formularios específicos por terapia.

**Base existente:** `Consulta`, `AnamnesisGeneral`, anamnesis de homeopatía/quiropraxia/acupuntura, `EvaluacionIntegralMbi`, `RegionCorporal`, `MapaCorporal`, `ZonaDolor` y archivos clínicos.

**Pantallas propuestas:**

- Bandeja de consultas por fecha, profesional, sede, especialidad y estado.
- Flujo de consulta con secciones: identificación/contexto, motivo e historia, antecedentes, signos vitales, evaluación, formularios de especialidad y cierre.
- Subformularios Livewire especializados; no acumular toda la lógica clínica en una única vista grande.
- Historial de consultas en expediente con enlaces a la evolución y adjuntos.

**Pasos:**

1. **Acordado parcialmente (2026-10-14):** cierre exige motivo y observaciones finales; `consultations.close` y `consultations.reopen` son permisos independientes. Pendiente precisar asignación de permisos a perfiles clínicos si se requiere.
2. **Implementado (2026-10-14):** transiciones `abierta`/`en_proceso` → `cerrada` y `cerrada` → `en_proceso` dentro de transacciones con auditoría. Cancelación queda fuera de este flujo.
3. **Implementado parcialmente (2026-10-14):** crear una consulta desde una cita confirmada en la ficha del paciente. Requiere `consultations.create` y sede, profesional y especialidad asignados. Recepción asigna estos tres valores al confirmar desde la agenda o solicitudes; modalidad walk-in pendiente.
4. **Implementado parcialmente (2026-10-14):** crear dentro de transacción y bloquear el expediente antes de `Consulta::siguienteNumero()`, serializando la numeración por expediente/especialidad. Se previene duplicar consultas por cita y se audita la apertura.
5. **Implementación previa (2026-10-14), sustituida el 2026-10-22:** inicialmente había tarjetas separadas para cada formulario; la experiencia actual integra general, Homeopatía y Quiropraxia en una anamnesis común y mantiene Acupuntura individual. Se conservan la autorización `consultations.edit`, la auditoría sin valores clínicos, los casts JSON y el IMC calculado por base de datos.
6. **Comportamiento preservado:** el cierre continúa separado del guardado de anamnesis; conserva sus requisitos, permisos, auditoría y tratamiento de borradores.
7. **Implementado (2026-10-22):** anamnesis integral en un único formulario para los incisos 01–06 del recurso `recursos IA/anamnesis.xlsx` (general, homeopática y quiropráctica/neuromusculoesquelética), con captura de contexto, antecedentes, revisión de sistemas y evaluación. Acupuntura permanece en un formulario individual con campos complementarios del recurso `NUEVO.xlsx`. Los datos complementarios se guardan con auditoría.
8. **Implementado (2026-10-22):** el resultado del evaluador, inciso 07, queda visible en la ficha del paciente a partir de los datos de cada consulta.
9. **Implementado (2026-10-24):** evaluación integral MBI independiente por consulta, con integración clínica, sistemas comprometidos, hallazgos, objetivo terapéutico, observaciones, especialidades y selección del Modelo 6+. Los campos se guardan en `evaluacion_integral_mbi`, con auditoría y permiso `consultations.edit`; las listas se capturan por líneas y se guardan como JSON. La selección de especialidades y Modelo 6+ permanece como texto libre hasta que el equipo clínico defina sus catálogos.
10. **Reemplazado (2026-10-26):** informe por sistemas y test de intoxicación ya no son componentes separados (el informe es el inciso 04 de la anamnesis integral; el test se elimina). Se rediseña el formulario según `recursos IA/MARVAS_especificacion_anamnesis_acupuntura_mapa_dolor.md`, editando las migraciones existentes (se pretende `migrate:fresh --seed`, sin migrar datos antiguos). Etapas:
    - **Etapa 1 (hecha 2026-10-26):** esquema y modelos. Se eliminan `informe_sistemas` y `test_intoxicacion`; nuevo catálogo `regiones_corporales` (las 20 regiones de `mapa-corporal.png`, ampliable); `mapas_corporales` (1 por consulta: estado, EVA máximo, zona de mayor dolor) y `zonas_dolor` (región, lateralidad, EVA 0–10, nivel derivado, tipos/irradiación/patrón en JSON, inicio, comentario) para el historial por sesión; columnas nuevas aditivas en anamnesis general (intensidad, 03 y 04 en JSON, PA sistólica/diastólica), quiropraxia (06, 06A, 06B, 06D, decisión) y acupuntura (pulsos JSON, movimiento de lengua); paciente con `contacto_emergencia` y edad calculada.
    - **Etapa 2 (hecha 2026-10-27):** UI de la anamnesis integral con selectores de tres estados (`app/Support/AnamnesisCatalog.php` + parcial `consultation/partials/state-block`): 03 antecedentes, 04 revisión por sistemas, 06/06A/06B/06C/06D de quiropraxia con lado y fuerza 0–5, presión sistólica/diastólica e intensidad 0–10. Se retiraron las columnas antiguas (antecedentes/hábitos/alergias/medicamentos/etc. de la general; booleanos y enums de quiropraxia) editando las migraciones 000022 y 000025. Regla 06D: con una alerta en «Sí» se muestra advertencia en vivo y no se puede marcar la manipulación como «indicada». Pendiente menor: alimentar la intensidad desde el EVA máximo del mapa (Etapa 4).
    - **Etapa 3 (hecha 2026-10-27):** formulario de acupuntura según la hoja `Acu`: `app/Support/AcupunctureCatalog.php` (catálogos y validación). Pulsos en JSON `pulsos` (12 posiciones con su órgano y calidad en texto libre con sugerencias), lengua multi-selección (forma, color, rasgos, saburra) más humedad y movimiento (`lengua_movimiento`), 8 reglas (un polo por eje), 6 niveles y 4 capas (nomenclatura estándar, erratas del Excel corregidas), Zang-Fu con generación y control autocalculados (ciclo Sheng/Ke) y protocolo distal/local/maestro (puntos, técnica, observaciones; columnas por confirmar con el terapeuta). Se retiraron las 12 columnas `pulso_*` editando la migración 000026. Se conservan `diagnostico_energetico` y `principio_terapeutico`. Pendiente menor: exclusividad «Normal»/«Ausente» en las listas y tabla maestra de los 5 elementos.
    - **Etapa 4 (hecha 2026-10-27):** componente `Consultation/PainMap` (SVG sobre `public/img/mapa-corporal.png` con zonas guardadas en `regiones_corporales.trazos`), CRUD `Clinical/BodyRegions` con trazador, historial por región con gráfico y comparación entre sesiones, `ConsultationService::savePainMap` con auditoría y alimentación de intensidad/zona principal de la anamnesis. Pendiente: afinar contornos en navegador.
    - Pendientes del documento (calidad de pulsos, erratas de niveles, protocolo) se dejan como JSON flexible.
11. Validar adjuntos, límites de tamaño/MIME, almacenamiento privado y permisos de descarga.
12. Añadir pruebas para relaciones, cálculos, numeración concurrente y autorización.

**Aceptación:** el profesional autorizado puede documentar una consulta sin alterar citas o consultas previas; la numeración se mantiene por expediente y especialidad; campos generados y datos clínicos se guardan correctamente. Los incisos 01–06 se capturan en la anamnesis integral, acupuntura mantiene su formulario individual, el resultado del evaluador y la evaluación MBI aparecen en la ficha del paciente. El cierre conserva su comportamiento y las pruebas comprueban el guardado integral, MBI y la visualización del evaluador.

### Módulo 5 — Clasificación, planes terapéuticos y evolución

**Objetivo:** traducir la evaluación integral en una ruta terapéutica individual y documentar resultados por sesión.

**Base existente:** `EvaluacionIntegralMbi`, `ClasificacionPaciente`, `PlanTratamiento`, `EvolucionClinica`, `PaquetePaciente` y `ExpedienteMaestro::clasificacionVigente()/planTratamientoVigente()`.

**Pantallas propuestas:**

- Evaluación integral y selección de sistemas/especialidades del Modelo 6+.
- Historial de clasificaciones con categoría, justificación, objetivo y responsable.
- Editor de plan con diagnóstico integrativo, terapias indicadas, frecuencia, sesiones, fase e indicaciones para casa.
- Vista de seguimiento con evolución por consulta: hallazgos, terapia aplicada, respuesta y próxima conducta.

**Pasos:**

1. Confirmar definiciones operativas del Modelo 6+ y categorías de clasificación con el equipo clínico.
2. Diseñar formularios que restrinjan valores a enums/especialidades válidas y no dependan únicamente de JSON libre.
3. Crear clasificación vinculada a evaluación y usuario responsable.
4. Crear/modificar plan en transacción, permitiendo historial y control de plan activo.
5. Registrar una evolución por consulta y profesional, enlazada a especialidad.
6. Mostrar plan vigente y evoluciones en el expediente.
7. Añadir pruebas para historial, asociación, permisos y transición de fases.

**Aceptación:** se puede explicar por qué se asignó una categoría, consultar versiones anteriores del plan y registrar resultados sin sobrescribir evoluciones previas.

### Módulo 6 — Consentimientos y documentos clínicos

**Objetivo:** gestionar versiones de consentimiento y archivos médicos con trazabilidad.

**Base existente:** `ConsentimientoPlantilla`, `ConsentimientoRegistro`, `ArchivoClinico`, relaciones desde consulta/expediente y campos para confirmación, firma/huella, IP, dispositivo y comprobante PDF.

**Pantallas propuestas:**

- Administración de plantillas y vigencias.
- Registro del consentimiento en el contexto de consulta.
- Expediente de documentos con filtros por tipo y fecha.
- Carga/descarga autorizada de archivos.

**Pasos:**

1. Definir texto legal, vigencia, versión y terapias cubiertas con el responsable de la clínica.
2. Definir si firma digital/huella es obligatoria o si basta el consentimiento informado registrado; no inferir validez legal del esquema.
3. Implementar captura de confirmaciones y versión exacta de plantilla.
4. Generar/guardar comprobante de forma privada, con nombre no predecible y acceso autorizado.
5. Incorporar carga y clasificación de documentos asociados a consulta o expediente.
6. Registrar quién cargó/descargó/aceptó y probar que no se acceda públicamente a archivos sensibles.

**Aceptación:** cada consentimiento apunta a la plantilla vigente usada y cada archivo conserva metadatos, propietario y permisos de acceso.

### Módulo 7 — Paquetes clínicos, pagos y facturación

**Objetivo:** asociar servicios o paquetes a pacientes y registrar saldos y cobros de forma verificable.

**Base existente:** `PaqueteClinico`, `PaquetePaciente`, `Pago`, `DatosFacturacion`, `Cita` y relaciones en expediente. `pagos.saldo` es una columna generada.

**Pantallas propuestas:**

- Catálogo de paquetes, terapias, número de sesiones y precios.
- Paquetes activos del paciente con sesiones consumidas, saldo y vencimiento.
- Registro de pago/abono y consulta de movimientos por expediente.
- Datos de facturación y comprobantes según el alcance contable acordado.

**Pasos:**

1. Acordar reglas de saldo, pagos parciales, devoluciones, moneda, impuestos y permisos de caja.
2. Implementar catálogo respetando `activo` y especialidades.
3. Asociar un paquete al expediente y plan; aplicar validaciones de sesiones y fechas.
4. Registrar pagos en transacción, verificando monto pendiente antes de guardar.
5. Vincular cobros a cita/paquete cuando corresponda, conservando quién cobró y cuándo.
6. Añadir búsquedas y reportes de pagos, sin exponer información financiera a roles clínicos que no la necesitan.
7. Probar pago parcial, pago completo, límites y reversos conforme a política.

**Aceptación:** saldo y sesiones consumidas concuerdan con movimientos persistidos, y un usuario sin permiso de caja no puede registrar ni alterar pagos.

### Módulo 8 — Inventario, compras y consumo en consulta

**Objetivo:** gestionar productos, compras, lotes/caducidades, stock por sucursal y consumo/venta relacionado con consultas.

**Base existente:** categorías, proveedores, productos, lotes, `StockSucursal`, órdenes de compra y detalle, movimientos de inventario y `ConsumoProductoConsulta`.

**Pantallas propuestas:**

- Catálogo de productos y categorías.
- Existencia por sucursal, alertas de mínimo y próximos vencimientos.
- Órdenes de compra con recepción parcial/completa.
- Ajustes/transferencias y consulta de movimientos.
- Registro de consumo de producto desde consulta, con distinción entre uso interno y producto cobrado.

**Pasos:**

1. Definir políticas de costo, lote, caducidad, FEFO, transferencias, devoluciones y ajustes.
2. Implementar productos/proveedores/categorías y stock por sucursal.
3. Implementar recepción de órdenes actualizando lotes, stock y movimientos en una transacción con bloqueo.
4. Implementar salidas y ajustes como movimientos; no modificar el stock silenciosamente.
5. Integrar consumo clínico a consulta y pago cuando sea vendible/cobrado.
6. Añadir paneles de reabastecimiento y caducidad.
7. Probar invariantes de cantidad, stock negativo y trazabilidad de referencia.

**Aceptación:** todo cambio de existencia deja movimiento auditable y el stock se mantiene coherente por producto, lote y sucursal.

### Módulo 9 — Notificaciones, recordatorios y automatización de recepción

**Objetivo:** ayudar a recepción a dar seguimiento a solicitudes y recordar próximas citas.

**Base existente:** tabla/modelo `Notificacion`, `recordatorio_enviado` en citas e ideas en `IDEAS.txt` sobre llamadas 2–3 días antes y envío de formulario por enlace.

**Trabajo propuesto:**

1. Definir canal (correo, SMS, WhatsApp u otro), consentimiento del paciente, costos, proveedor e identidad emisora.
2. Crear tarea/cola con idempotencia y reintentos visibles; no marcar como enviado si el proveedor falla.
3. Programar recordatorio según la política acordada y zona horaria.
4. Implementar enlace seguro de formulario con expiración y alcance limitado; evitar enlaces que permitan enumerar o modificar otros pacientes.
5. Registrar intentos, resultado, actor/canal y estado para que recepción pueda intervenir.
6. Añadir pruebas con proveedor simulado y fallos/reintentos.

**Aceptación:** cada notificación registra resultado real y no duplica envíos tras reintentos; un enlace vencido o alterado no expone ni modifica expedientes.

### Módulo 10 — Dashboard y reportes operativos

**Objetivo:** reemplazar los placeholders del dashboard con indicadores autorizados y útiles.

**Base existente:** `resources/views/dashboard.blade.php` es una estructura inicial; existen citas, consultas, pagos, stock, notificaciones y auditoría.

**Trabajo propuesto:**

1. Acordar métricas por perfil (recepción, profesional, administración, caja).
2. Definir periodos, zona horaria, filtros por sucursal y reglas de acceso.
3. Mostrar resúmenes de citas pendientes, citas del día, confirmaciones, consultas y alertas de inventario cuando corresponda.
4. Optimizar agregaciones y evitar cargar información clínica identificable si el indicador no la necesita.
5. Probar rangos de fechas, permisos y resultados frente a datos de prueba.

**Aceptación:** los indicadores concuerdan con sus fuentes y cada rol ve únicamente los datos e indicadores permitidos.

### Módulo 11 — Sitio público y reserva en landing page

**Objetivo:** mantener la identidad y contenido de MARVAS y mejorar la conversión de visita a solicitud.

**Base existente:** portada, páginas de servicios, layout público y `AgendarCita`.

**Trabajo propuesto:**

1. Revisar textos, nombres consistentes de terapias (incluida la variante actual “iridiología/iridología”), accesibilidad y enlaces.
2. Alinear validaciones visuales y de servidor, estados de carga/éxito/error y privacidad de datos.
3. Hacer que la reserva cree una solicitud con estado `solicitada` y conserve el mensaje original.
4. **Implementado (2026-10-22):** forzar el tipo `primera_vez` en la reserva pública, sin selector. El seguimiento se agenda únicamente desde los flujos del personal.
5. Mostrar únicamente disponibilidad real según las reglas compartidas con la agenda.
6. Añadir pruebas de envío válido, validación, horario vencido/ocupado y falla transaccional.

**Aceptación:** el visitante entiende que envió una solicitud (no que ya tiene cita confirmada), siempre solicita una cita `primera_vez`, recibe respuesta clara y la solicitud llega a recepción.

### Módulo 12 — Catálogos clínicos administrativos

**Objetivo:** permitir que Sysadmin mantenga sedes, especialidades y perfiles profesionales sin alterar la gestión de cuentas, roles y permisos.

**Base existente:** modelos `Sucursal`, `Especialidad` y `Profesional`; relaciones profesionales-sedes/especialidades con indicador principal; cuentas y permisos Spatie.

**Trabajo y reglas:**

1. **Implementado (2026-10-24):** CRUD Livewire para crear/editar y activar/desactivar sedes y especialidades. No se eliminan físicamente registros históricos; `slug` de especialidad es único.
2. **Implementado (2026-10-24):** creación/edición de profesionales enlazando una cuenta existente sin perfil, con al menos una sede y especialidad y una asignación principal para cada relación.
3. La cuenta, su rol y sus permisos se crean/asignan desde la administración de usuarios por Sysadmin, nunca desde el CRUD profesional.
4. Las páginas administrativas están protegidas por permisos; Sysadmin obtiene acceso a catálogos y Recepción/Sysadmin pueden crear citas internas desde la agenda.
5. La creación de una cita directamente concertada por Recepción queda `confirmada`; primera visita requiere el Evaluador MBI configurado por sede.
6. **Implementado (2026-10-24):** los tres CRUD siguen el patrón de `Admin/Pages`: trait `DataTable`, `<x-data-table>` con `@interact`, y trait `Searchable` (`filterAdvance`) en `Sucursal`, `Especialidad` y `Profesional`. Se mantiene activar/desactivar en lugar de eliminar.

**Aceptación:** solo Sysadmin puede modificar los catálogos; las relaciones principales y las reglas de asignación son válidas, los historiales se conservan al desactivar, y la cita interna queda confirmada únicamente tras validación transaccional de paciente, recursos y horario.

## 5. Orden sugerido de entrega

1. **Fase A — cerrar ciclo mínimo de cita:** Módulo 0 (permisos mínimos), Módulo 1 y Módulo 11. Entregar reserva → bandeja → llamada/edición → confirmación.
2. **Fase B — agenda segura y operación diaria:** Módulos 2 y 12. La disponibilidad global se conserva; reservas internas y cambios de asignación se validan en el servidor.
3. **Fase C — identidad e historia clínica:** Módulos 3 y 4.
4. **Fase D — decisiones terapéuticas:** Módulo 5.
5. **Fase E — operación clínica segura:** Módulo 6.
6. **Fase F — caja e inventario:** Módulos 7 y 8.
7. **Fase G — seguimiento y visibilidad:** Módulos 9 y 10.

No iniciar módulos de fase posterior si falta su contrato de datos o permisos; se puede paralelizar diseño y pruebas de bajo acoplamiento, pero no permitir que dos agentes modifiquen a la vez los mismos archivos centrales sin coordinarlo.

## 6. Contrato de trabajo para múltiples agentes

### Antes de empezar

1. Leer este archivo completo y las secciones recientes de [AVANCES.md](./AVANCES.md).
2. Comprobar `git status` y leer los archivos del módulo antes de editar. El árbol de trabajo puede contener cambios no confirmados de otros agentes o del usuario.
3. Elegir una tarea concreta del módulo con resultado verificable. Confirmar que no está marcada como en curso por otra persona.
4. Registrar en `AVANCES.md` el agente/identificador disponible, alcance exacto, archivos que pretende tocar y dependencias.
5. Si una regla de negocio no está definida, registrar la decisión pendiente y no inventar conductas irreversibles.

### Durante el trabajo

- Mantener los cambios dentro del módulo asignado; comunicar antes de tocar un archivo compartido (rutas, modelos base, layouts, permisos, migraciones centrales).
- No modificar código ajeno o cambios locales no relacionados.
- Reutilizar modelos, relaciones, componentes, traits, filtros, permisos y convenciones existentes.
- **Regla obligatoria de CRUD:** todo CRUD nuevo o modificado debe seguir el patrón de `app/Livewire/Admin/Pages.php`: trait `App\Traits\DataTable` en el componente (`$headers`, `rows()` computada con `filterAdvance`), vista con el componente `<x-data-table>` y la directiva `@interact` para columnas personalizadas y acciones, y trait `App\Traits\Searchable` en los modelos listados. No crear tablas HTML manuales ni paginación propia.
- **Regla obligatoria de comentarios en clases Livewire:** cada método de un componente Livewire (acciones públicas, `rows()`, hooks `updated*`, `mount`, `render`, métodos privados) debe llevar un comentario explicativo **con un ejemplo de uso**, además del DocBlock/PHPDoc. Se sigue el estilo de `resources/views/components/calendar.blade.php`: qué hace, por qué se hace así (si no es obvio), cómo se invoca/recibe datos y un ejemplo breve de la llamada o del resultado. El ejemplo va indentado dentro del comentario. Ejemplo:

  ```php
  /**
   * Activa o desactiva una sede sin borrarla (conserva el historial).
   *
   * @param  int  $id  Identificador de la sede.
   */
  // Alterna el indicador `activa`; no hay borrado físico porque las citas
  // históricas dependen de la sede.
  //   Desde la vista:
  //     <flux:menu.item wire:click="toggleActive({{ $row->id }})">Desactivar</flux:menu.item>
  //   Resultado: Sucursal #3 pasa de activa=1 a activa=0 y se muestra un toast.
  public function toggleActive(int $id): void
  ```

  Los comentarios deben mantenerse al modificar el método. Aplica a código nuevo y a los métodos que se toquen; no se exige comentar masivamente código antiguo no modificado.
- **Regla obligatoria de permisos:** todo permiso nuevo (ver página `page.view.*` o acción) debe registrarse en `database/seeders/PermissionsAccessSeeder.php` (constantes `PAGE_PERMISSIONS`, `ACTION_PERMISSIONS` y `ROLE_PERMISSIONS`), que `DatabaseSeeder` invoca. No crear permisos fuera de los seeders ni dispersarlos en componentes o migraciones. Las páginas de menú se registran en el seeder de acceso del módulo y su permiso en `PermissionsAccessSeeder`. Los permisos antes dispersos en `AdminAccessSeeder`, `AgendaAccessSeeder`, `SolicitudesAccessSeeder`, `PacientesAccessSeeder` y `ClinicalCatalogAccessSeeder` ya fueron trasladados allí (incluida la asignación a Recepcionista y Sysadmin); esos seeders solo registran páginas de menú.
- Añadir pruebas de comportamiento junto con la función, no como tarea indefinida posterior.
- Tratar la documentación como coordinación, no como sustituto de pruebas ni evidencia de implementación.

### Al terminar

1. Ejecutar pruebas y validaciones más pequeñas que cubran el cambio; anotar comando y resultado exactos.
2. Revisar `git diff` para verificar que solo estén los cambios propios.
3. Actualizar `AVANCES.md`: estado, descripción verificable, archivos tocados, pruebas, decisiones, bloqueos y siguiente tarea concreta.
4. Si algo no se terminó, marcarlo como parcial/en curso y dejar instrucciones reproducibles; no marcarlo como completo.
5. No sobrescribir el historial de otro agente ni afirmar que algo fue integrado/confirmado en Git sin comprobarlo.

### Estados permitidos

- `Pendiente`: no iniciado.
- `En curso`: una persona/agente tiene alcance y archivos registrados.
- `Bloqueado`: requiere una decisión, dependencia o acceso; explicar qué falta.
- `Parcial`: hay código o análisis, pero falta aceptación/prueba/integración.
- `Completo`: criterios de aceptación verificados y pruebas anotadas.

## 7. Decisiones de negocio que deben quedar registradas

Antes de implementar el comportamiento correspondiente, anotar respuesta, responsable y fecha en [AVANCES.md](./AVANCES.md):

1. ¿La cita del formulario es una solicitud pendiente o queda confirmada automáticamente? Propuesta del plan: solicitud `solicitada`, confirmada por recepción.
2. ¿El horario se comparte entre toda la clínica o se reserva por profesional, especialidad y sucursal?
3. ¿Qué resultado de llamada se registra y cuánto tiempo puede permanecer pendiente una solicitud?
4. ¿Recepción puede enlazar la solicitud a un paciente existente? ¿Qué validación humana exige la clínica?
5. ¿Qué datos del paciente son obligatorios antes de confirmar y cuáles pueden completarse después?
6. ¿Qué canal se usará para confirmaciones y recordatorios? ¿Qué consentimientos son necesarios?
7. ¿Qué roles pueden consultar cada sección clínica, financiera y de inventario?
8. ¿Cuáles son las reglas de paquetes, saldo, cancelaciones, reembolsos, impuestos y uso de inventario?

## 8. Definición general de terminado

Un módulo solo puede marcarse completo cuando:

- cumple los criterios de aceptación indicados;
- sus rutas y acciones tienen autorización del lado servidor;
- valida entradas y muestra errores reales sin respuestas de éxito engañosas;
- mantiene transacciones e invariantes del dominio;
- conserva trazabilidad de datos sensibles y archivos;
- tiene pruebas acordes a los riesgos del flujo;
- no rompe los módulos existentes y sus pruebas aplicables pasan;
- `AVANCES.md` describe con exactitud lo implementado y el siguiente punto pendiente.
