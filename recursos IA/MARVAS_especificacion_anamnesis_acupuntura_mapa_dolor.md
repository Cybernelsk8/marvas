# MARVAS – Especificación de formularios clínicos (versión para desarrollo)

Documento para entregar a una IA/desarrollador que construirá el sistema clínico (homeopatía, quiropráctica y acupuntura) de **MARVAS – Instituto de Medicina Biológica Integral**. Se basa en dos Excel:

| Archivo | Alcance en esta versión |
|---|---|
| `anamnesis.xlsx` | **Se usa completo** (Parte A), con **una modificación**: el campo "MAPA DE DOLOR" se reemplaza por un módulo interactivo de imagen corporal (Parte C). |
| `NUEVO.xlsx` | **Solo la pestaña `Acu`** (Parte B). Las demás pestañas se omiten. |

> **Leyenda.** "Hecho" = está literalmente en el Excel. "Sugerido" / "inferido" = lo agrego para que sea implementable; el Excel no lo dice.

---

# Convenciones y catálogos reutilizables

### Tipos de campo que usa el formulario
- **Texto corto**: una línea (nombre, DPI, teléfono).
- **Texto largo**: caja de texto libre (motivos, descripciones).
- **Selector (lista desplegable)**: valores fijos. Son los únicos "campos cerrados" reales del Excel.
- **Casilla / marca**: en `Acu` las listas de opciones (lengua, niveles, capas) son casillas para marcar; el Excel solo trae los rótulos.
- **Escala numérica**: 0-10 (dolor) o 0-5 (fuerza muscular).

### Catálogos de valores cerrados (hecho, de `anamnesis.xlsx`; **más el nuevo catálogo de dolor** en la Parte C)
| Catálogo | Valores | Se usa en |
|---|---|---|
| **TRI_SI_NO** | `No`, `Sí`, `No evaluado` | 03 Antecedentes, 06 Síntomas quiroprácticos, 06D Seguridad |
| **SINTOMAS** | `Sin síntomas`, `Con síntomas`, `No evaluado` | 04 Revisión por sistemas |
| **EXPLORACION_A** | `Normal`, `Alterado`, `No evaluado` | 06A Postura/marcha/movilidad |
| **EXPLORACION_B** | `Normal`, `Alterado`, `No realizado` | 06B Neurológica/ortopédica |
| **INDICE_FUNCIONAL** | `Bueno`, `Ligero`, `Moderado`, `Malo` | 06C Índice funcional |

**Idea clave del diseño:** casi todo selector tiene una opción **"No evaluado" / "No realizado"**. Eso distingue *"se preguntó y es No"* de *"no se preguntó"*. En base de datos conviene modelarlo como valor de 3 estados, **no** como booleano.

### Patrón visual de las filas (hoja `anamnesis.xlsx`)
Columnas: `B` rótulo · `C:D` (o `C:F`) selector · `E` separador estrecho · `F` guía de qué escribir · `G:H` detalle libre.
- En 06 y 06B: `B` = pregunta, `C:D` = selector, `F` = **texto guía** (placeholder, p. ej. "Localización, frecuencia, asociación cervical"), `G:H` = detalle libre.
- En cajas de texto largo: una fila de rótulo (combinada B:H) y debajo una fila-caja (combinada B:H).


---

# PARTE A – `anamnesis.xlsx` (Anamnesis Clínica Integral)

Título: **"MARVAS | ANAMNESIS CLÍNICA INTEGRAL"** · Subtítulo: *"HOMEOPATÍA + QUIROPRÁCTICA · registro único · marcar No evaluado cuando corresponda"*.

## 01 · IDENTIFICACIÓN
Todos son texto libre (sin selector). Valores sugeridos entre paréntesis.

| Campo | Tipo | Valores / notas |
|---|---|---|
| N.º expediente | texto corto | Identificador único del paciente |
| Fecha de consulta | fecha | |
| Nombre completo | texto corto | |
| Fecha de nacimiento / edad | fecha + edad | Un solo campo en el Excel; en el sistema: guardar fecha y **calcular** edad |
| Sexo | texto corto | Sugerido: Masculino / Femenino / Otro |
| DPI | texto corto | Documento personal de identificación (Guatemala), 13 dígitos |
| Dirección | texto largo | |
| Teléfono / emergencia | texto corto | Dos datos en un campo: teléfono personal + contacto de emergencia (sugerido separarlos) |
| Estado civil | texto corto | Sugerido: Soltero/a, Casado/a, Unión libre, Divorciado/a, Viudo/a |
| Ocupación | texto corto | |
| Religión (opcional) | texto corto | Opcional |
| Terapeuta responsable | texto corto | Sugerido: referencia a un usuario/profesional |

## 02 · CONSULTA ACTUAL
| Campo | Tipo | Valores / notas |
|---|---|---|
| Motivo principal (palabras del paciente) | texto largo | Debe transcribirse con las palabras del paciente |
| Inicio / fecha o tiempo de evolución | texto corto | Fecha exacta o "hace 3 semanas" |
| Forma de inicio | texto | Sugerido: súbito, gradual, traumático, insidioso |
| Frecuencia | texto | Sugerido: constante, intermitente, diario, ocasional |
| Evolución | texto | Sugerido: mejorando, estable, empeorando, fluctuante |
| Intensidad 0-10, zona, momento | número 0-10 + texto | Escala EVA 0–10; más zona y momento del día |
| Hormigueo / adormecimiento | texto | Zona y distribución |
| Debilidad, extremidad y lado | texto | Extremidad + lateralidad (derecha/izquierda/bilateral) |
| Irradiación hacia qué región | texto | |
| Localización anatómica, tipo de dolor, irradiación, horario, duración y patrón | texto largo | |
| Agravantes, alivio, tratamientos previos y respuesta | texto largo | |

## 03 · ANTECEDENTES Y HÁBITOS
**Bloque con selector TRI_SI_NO + detalle** (el selector está en `C`, el detalle en el resto de la fila):

| Campo | Detalle que se pide cuando es "Sí" |
|---|---|
| Enfermedades crónicas y previas | cuáles |
| Cirugías | tipo de cirugía / año |
| Hospitalizaciones | motivo / fecha |
| Traumatismos / fracturas | región / fecha |
| Alergias | sustancia / reacción |
| Medicamentos actuales | nombre / dosis / frecuencia |
| Implantes metálicos / prótesis | tipo / localización |
| Embarazo posible (si aplica) | semanas / fecha de última menstruación |

**Texto largo:** *Antecedentes familiares* — cáncer, diabetes, hipertensión, artritis, artrosis, hernias, Alzheimer, osteoporosis; indicar parentesco y tratamiento.

**Hábitos (texto libre, sin selector):**
| Campo | Detalle |
|---|---|
| Tabaco | cantidad / frecuencia |
| Alcohol | cantidad / frecuencia |
| Café | tazas por día |
| Otras sustancias | tipo / frecuencia |
| Dieta y apetito | patrón alimentario / cambios |
| Hidratación | (el rótulo repite por error "Patrón alimentario / cambios"; debería ser vasos/litros al día) |
| Actividad física | tipo / veces por semana |
| Sueño | horas / calidad |
| Estrés | nivel / factores asociados |
| Condiciones laborales / ergonomía | texto |

## 04 · REVISIÓN POR SISTEMAS Y EXAMEN GENERAL
Encabezado de tabla: **SISTEMA | SÍNTOMAS ORIENTATIVOS (centro) | HALLAZGOS / SÍNTOMAS REFERIDOS (cuadro derecho)**.
Cada sistema tiene: selector **SINTOMAS** + texto libre de hallazgos. El rótulo trae entre paréntesis los síntomas a preguntar.

| # | Sistema | Síntomas orientativos (guía) |
|---|---|---|
| 1 | Piel y anexos | erupciones, masas, prurito, sequedad, coloración, pelo, uñas, sudoración |
| 2 | Cabeza y cuello | cefalea, heridas, masas, bocio, rigidez |
| 3 | Ojos | lentes, último examen, dolor, enrojecimiento, lagrimeo, diplopía, visión |
| 4 | Oídos / equilibrio | acúfenos, vértigo, otalgia, infecciones, secreciones |
| 5 | Nariz y senos | resfriados, congestión, rinorrea, prurito, sinusitis |
| 6 | Boca / garganta | dientes, encías, sangrado, dentista, dolor, disfagia, infecciones |
| 7 | Mamas | masas, dolor, secreción, autoexploración (si corresponde) |
| 8 | Respiratorio | tos, esputo, hemoptisis, sibilancias, asma, bronquitis, neumonía, disnea |
| 9 | Cardiovascular | HTA, soplos, dolor torácico, palpitaciones, edema, disnea |
| 10 | Gastrointestinal | ardor, náusea, vómito, hematemesis, digestión, estreñimiento, diarrea |
| 11 | Óseo-muscular | dolor, debilidad, rigidez, deformidad, limitación de movimiento |
| 12 | Urinario | frecuencia, poliuria, nicturia, disuria, hematuria, urgencia, chorro |
| 13 | Reproductor masculino | hernias, secreción, lesiones, dolor o masa testicular, ITS (si corresponde) |
| 14 | Reproductor femenino | menarquia, ciclos, sangrado, dolor, flujo, gestaciones (si corresponde) |
| 15 | Neurológico | convulsiones, debilidad, parálisis, parestesias, temblor |

**Signos vitales y medidas** (rótulo + "Valor / observación"):
| Campo | Unidad | Tipo recomendado |
|---|---|---|
| PA | mmHg | sistólica/diastólica (dos enteros) |
| FC | lpm | entero |
| FR | rpm | entero |
| Temperatura | °C | decimal |
| Glucemia (si aplica) | mg/dL (sugerido) | decimal |
| Peso | kg | decimal |
| Talla | cm | decimal |

> ⚠ **Defecto del Excel:** la validación de lista "Sin síntomas / Con síntomas / No evaluado" cubre `C54:C65` **y también `C66:C75`**, que incluye reproductor masc./fem., neurológico **y los 7 signos vitales**. Para vitales el selector no tiene sentido: deben ser numéricos (con "No evaluado" como estado aparte).

**Texto largo:** *Exámenes complementarios aportados y resultados relevantes.*

## 05 · EVALUACIÓN HOMEOPÁTICA (4 cajas de texto largo)
1. Síntomas locales y generales característicos: localización, modalidades, concomitantes.
2. Síntomas mentales: ánimo, nerviosismo, tensión, temores, irritabilidad, concentración.
3. Patrones de sueño, sueños, sensibilidad térmica, sed, apetito, preferencias y aversiones.
4. Biopatografía: enfermedades infantiles, inmunizaciones, hospitalizaciones, eventos vitales.

## 06 · EVALUACIÓN QUIROPRÁCTICA Y NEUROMUSCULOESQUELÉTICA
Nota del Excel: *"Síntomas exclusivos y exploración física; los antecedentes compartidos están en 03 y 04"* (no se repiten datos).

**Síntomas (selector TRI_SI_NO + detalle libre; entre paréntesis la guía de detalle):**
Cefalea recurrente (localización, frecuencia, asociación cervical) · Uso de lentes de contacto (tiempo y motivo) · Alteraciones del equilibrio (vértigo, inestabilidad, caídas) · Ortodoncia / prótesis dental (tipo, fecha) · Síntomas ATM / bruxismo (dolor, chasquido, bloqueo, apertura) · Infecciones de garganta recurrentes (frecuencia, antecedentes) · Zumbido de oído (lateralidad, inicio) · Parálisis facial previa (lado, fecha, secuelas) · Dolor ciático (lado, trayecto, parestesias) · Adormecimiento corporal (zona, distribución, progresión) · Problemas dermatológicos (localización) · Cervicalgia (irradiación, rigidez, intensidad) · Dorsalgia (localización, respiración, postura) · Lumbalgia (irradiación, postura, carga) · Dolor sacroilíaco / pélvico (lateralidad, marcha) · Dolor hombros / brazos / manos (lado, movimiento) · Dolor cadera / rodilla / pies (lado, marcha) · Dolor nocturno / en reposo (despertar, progresión). → **18 ítems.**

**MAPA DE DOLOR** → **modificado: ahora es un módulo interactivo.** Ver la sección *"Módulo MAPA DE DOLOR"* en la Parte C. Reemplaza al antiguo texto libre.

### 06A · Postura, marcha y movilidad (selector EXPLORACION_A + descripción libre)
Posición de la cabeza (anteriorización, inclinación o rotación) · Alineación de hombros (simetría, elevación D/I) · Curvaturas vertebrales (cifosis, lordosis y región) · Alineación del tronco (asimetría/desviación) · Alineación pélvica (simetría de crestas ilíacas) · Marcha y apoyo plantar · Equilibrio (prueba realizada y estabilidad) · Movilidad cervical (flexión, extensión, rotación, inclinación) · Movilidad dorsal (rotación y extensión) · Movilidad lumbar (flexión, extensión, inclinación) · Movilidad de extremidades (articulación, lado, movimiento) · Palpación muscular (zona, sensibilidad, espasmo). → **12 ítems.**

> ⚠ **Defecto del Excel:** en esta sección el rótulo está combinado en `B:D`, pero la lista desplegable se definió en `C110:C121`, que queda *dentro* de la celda combinada: **en Excel el selector no es utilizable**. En el sistema real, cada ítem debe tener selector + descripción.

### 06B · Exploración neurológica y ortopédica (selector EXPLORACION_B + "Lado / resultado / detalle")
Fuerza muscular segmentaria **(escala 0-5)** · Sensibilidad por dermatomas · Reflejos osteotendinosos · Elevación de pierna recta (**Lasègue**) · **Slump** · **Spurling** / provocación cervical · Pruebas sacroilíacas según indicación · Pruebas de hombro / cadera según indicación. → **8 ítems.**
Más: *Otras pruebas efectuadas, resultados, interpretación y limitaciones de exploración* (texto largo).
Sugerido: cada prueba capturar **lateralidad** (D / I / bilateral) y para fuerza el valor 0-5 por segmento.

### 06C · Índice funcional (selector INDICE_FUNCIONAL: Bueno / Ligero / Moderado / Malo)
Calidad del sueño (afectación/comentarios) · Desplazamiento (distancia/tolerancia) · Trabajo (actividad/restricción) · Recreación (actividad/limitación).
> En el Excel la celda está combinada hasta H, así que **no hay espacio para el comentario** que el rótulo promete.

### 06D · Seguridad antes de manipulación (bandera roja)
Aviso: *"Un hallazgo de alarma requiere valoración clínica y posible referencia; no realizar manipulación hasta descartar contraindicaciones."*
**Selector TRI_SI_NO** en cada uno de 9 criterios:
1. Déficit neurológico nuevo o progresivo
2. Alteración de esfínteres / anestesia en silla de montar
3. Trauma importante / sospecha de fractura
4. Fiebre o sospecha de infección espinal
5. Cáncer conocido con dolor nuevo inexplicado
6. Osteoporosis severa / fragilidad ósea
7. Anticoagulación / sangrado relevante
8. Síntomas de alarma vascular cervical
9. Cirugía reciente / inestabilidad conocida

**Decisión de seguridad** (texto largo): *manipulación* **indicada**, **diferida** o **contraindicada**; motivo y referencia. → En el sistema: enum de 3 valores + texto. Regla útil: cualquier "Sí" debería bloquear/advertir antes de registrar una manipulación.

## 07 · IMPRESIÓN CLÍNICA Y PLAN (4 cajas de texto largo)
1. Resumen de síntomas referidos, hallazgos objetivos y valoración diferenciada.
2. Diagnóstico médico documentado (si existe), hipótesis clínica y diagnósticos diferenciales.
3. Objetivos, intervenciones previstas, precauciones, consentimiento y referencia.
4. Plan de seguimiento, reevaluación, evolución y **firma del terapeuta**.

---


---

# PARTE B – `NUEVO.xlsx`, solo pestaña `Acu`

> De `NUEVO.xlsx` **se usa únicamente la pestaña `Acu`**. Las pestañas `TEST INTOXICACIÓN`, `HOMEO`, `QUIRO` e `INFORME POR SISTEMAS` se omiten (su contenido relevante ya está en la anamnesis unificada).

## B · Hoja `Acu` – Diagnóstico en acupuntura (medicina tradicional china)
Esta hoja no tiene equivalente en `anamnesis.xlsx`. Las listas de opciones aparecen sin casillas visibles: asumir **casillas de marcar**. (Inferido: las listas de forma, color, capa lingual y humedad se pueden marcar más de una a la vez salvo "Normal"/"Ausente".)

### B.1 Pulsos
Tabla con dos manos; cada mano tiene 3 posiciones (**Cun, Guan, Chi**) × 2 profundidades (**Superficial / Profundo**) = 12 puntos de pulso. Cada celda trae el órgano que corresponde:

| Posición | MANO DERECHA superficial | MANO DERECHA profundo | MANO IZQUIERDA superficial | MANO IZQUIERDA profundo |
|---|---|---|---|---|
| **Cun** | IG (intestino grueso) | P (pulmón) | ID (intestino delgado) | C (corazón) |
| **Guan** | E (estómago) | Bazo-P (bazo/páncreas) | V Biliar | I (hígado, escrito "I" en el Excel) |
| **Chi** | TR (triple recalentador) | MC (maestro corazón/pericardio) | V (vejiga) | R (riñón) |

Lo que se registra en cada punto **no está definido en el Excel** (no hay catálogo). Sugerido: calidad del pulso (p. ej. normal, vacío, lleno, rápido, lento, tenso/cuerda, resbaladizo, superficial, profundo) o una intensidad; lo mejor es dejarlo como texto con lista editable que defina el terapeuta.

### B.2 Diagnóstico de lengua (2 secciones en el Excel, ambas rotuladas "DIAGNÓSTICO DE LENGUA")
**Sección 1 – forma y color**
| Grupo | Opciones |
|---|---|
| Forma de la lengua | Normal · Delgada · Hinchada · Dentada (marcas de dientes) · Puntiaguda · Rajada o agrietada · Deforme |
| Color de la lengua | Pálida · Roja · Roja intensa · Púrpura · Azulada · Manchas visibles |
| Otros rasgos | Puntos rojos · Úlceras · Venas sublinguales prominentes · Patrones inusuales (texto) |

**Sección 2 – capa, humedad y movimiento**
| Grupo | Opciones |
|---|---|
| Capa lingual (saburra) – color | Blanca · Amarilla · Grisácea · Marrón · Ausente |
| Capa – espesor | Delgada · Gruesa |
| Capa – distribución | Uniforme · Irregular |
| Humedad | Seca · Húmeda · Pegajosa |
| Movimiento de la lengua | Normal · Rígida · Temblorosa · Débil · Desviada · Retraída |

**Imágenes de apoyo en la hoja (no son datos, son guía visual):**
- *Mapa de la lengua por órganos y elementos* (imagen junto al diagnóstico de lengua): zonas de la lengua: **Agua** (riñones–vejiga, arriba/raíz), **Madera** (hígado–vesícula biliar, laterales medios), **Tierra** (bazo–estómago, centro), **Metal** (pulmón–intestino grueso, centro-anterior), **Fuego** (corazón–intestino delgado, punta). Cada zona con emociones y tejidos asociados.
- *Tabla "Asociaciones de los cinco elementos"* (imagen con logo MARVAS): para Madera/Fuego/Tierra/Metal/Agua → órganos (zang/fu), funciones, sentidos, tejidos, emociones equilibradas y desequilibradas, sabor, estación, clima, color, dirección, manifestaciones en la lengua, otras asociaciones. Es **material de referencia** que puede convertirse en una tabla maestra de la base de datos.
- *Diagrama de los cinco elementos* (pentágono): Fuego–Sur–Verano; Tierra–Centro–Final de verano; Metal–Otoño; Agua–Norte–Invierno; Madera–Este–Primavera, con sentidos y emociones (Madera: ira/coraje; Fuego: alegría-euforia; Tierra: reflexión-obsesión; Metal: orgullo-tristeza; Agua: miedo-prudencia) y flechas del **ciclo de generación (Sheng)** y **control (Ke)**.

### B.3 Diagnóstico de las 8 reglas
Cuatro ejes polares; se elige **un polo por eje**:
| Eje | Polo A | Polo B |
|---|---|---|
| Localización | **Interno** | **Externo** |
| Temperatura | **Frío** | **Calor** |
| Fuerza | **Vacío** | **Plenitud** |
| Polaridad | **Yin** (se vincula a "6 Niveles") | **Yang** (se vincula a "4 Capas") |

### B.4 Diagnóstico de los 6 niveles (meridianos)
Marcar el nivel afectado. Tabla tal como aparece:

| Grupo | Nivel | Órgano/meridiano |
|---|---|---|
| **Mano Yin** | Tai Yin* (en el Excel dice "Tai Yang") | Pulmón |
| | Jue Yin | MC (Maestro Corazón) |
| | Shao Yin | C (Corazón) |
| **Pie Yin** | Tai Yin* (en el Excel "Tai yij") | Bazo-P |
| | Jue Yin | H (Hígado) |
| | Shao Yin | R (Riñón) |
| **Mano Yang** | Yang Ming | IG |
| | Shao Yang | TR |
| | Tai Yang* (en el Excel "Tao yang") | ID |
| **Pie Yang** | Yang Ming | E (Estómago) |
| | Shao Yang | V Biliar |
| | Tai Yang* (en el Excel "Tao yang") | V (Vejiga) |

\* **Erratas del Excel** (corregidas aquí según la nomenclatura estándar de los 6 niveles: Tai Yin, Jue Yin, Shao Yin, Yang Ming, Shao Yang, Tai Yang). Conviene confirmarlas con el terapeuta antes de cargarlas como catálogo.

### B.5 Diagnóstico de las 4 capas
Marcar la(s) capa(s) afectada(s):
| Capa | Órganos asociados (según el Excel) |
|---|---|
| Qi defensivo | IG / P |
| Qi energético | Riñón / Vejiga |
| Qi nutritivo | Bazo-P / ID / Estómago |
| Qi Xue (sangre) | Corazón / Hígado |

### B.6 Órganos afectados Zang-Fu
Tabla de **3 columnas × 13 filas** en blanco: **GENERACIÓN O NUTRICIÓN** (órgano "madre") · **ÓRGANOS AFECTADOS** · **CONTROL** (órgano que lo controla). Junto a la tabla va el pentágono de los cinco elementos para deducir cada relación (ciclo Sheng/Ke). Sugerido: seleccionar el órgano afectado y que el sistema **autocalcule** madre y controlador con la tabla de los 5 elementos.

### B.7 Protocolo
Tres bloques, cada uno con 3 celdas de captura sin encabezado (el Excel no las rotula):
| Bloque | Qué contiene |
|---|---|
| **Puntos distales** | puntos de acupuntura lejos de la zona |
| **Puntos locales** | puntos sobre/cerca de la zona afectada |
| **Puntos maestro** | puntos maestros (p. ej. de los ocho vasos extraordinarios) |

Inferido: las 3 celdas = *puntos* (códigos tipo "IG4", "H3"), *técnica/método* y *observaciones*; confirmar con el terapeuta. Sugerido: catálogo de puntos de acupuntura con código de meridiano.


---

# PARTE C – Módulo MAPA DE DOLOR (nuevo, reemplaza el campo de texto de la sección 06)

## C.1 Objetivo
Dos figuras del cuerpo con musculatura, **vista anterior y vista posterior**. El terapeuta (o el paciente) toca una zona concreta; el sistema abre un panel que pide **evaluar el nivel de dolor** y algunos datos más. Se pueden marcar varias zonas. Cada zona marcada se pinta con el color de su nivel.

**Imagen:** en el Excel original hay una ilustración muscular frontal y posterior (hombre, sin etiquetas). Para que las zonas sean tocables, **dibujar las regiones como polígonos/SVG sobre la figura** (una región = un `path` con un `id`). Recomendación: usar una ilustración propia o con licencia, con siluetas simples y zonas bien delimitadas, y no depender de recortar la imagen original.

## C.2 Escala de dolor (propuesta)
Tu ejemplo (leve, intenso, doloroso, muy doloroso) tiene un problema: "intenso" y "doloroso" se confunden y no hay nivel intermedio claro. Propongo **5 niveles ordenados**, ligados a la escala **EVA 0-10** que ya usa el formulario (campo "Intensidad 0-10" y "intensidad EVA"):

| Nivel (etiqueta) | Rango EVA | Descripción para el paciente | Color sugerido |
|---|---|---|---|
| **Sin dolor** | 0 | No siente molestia en esta zona | gris / sin color |
| **Leve** | 1–3 | Molesta pero puede ignorarlo; no limita actividades | amarillo |
| **Moderado** | 4–6 | Se nota todo el tiempo; limita algunas actividades | naranja |
| **Intenso** | 7–8 | Difícil de ignorar; limita la mayoría de actividades | rojo |
| **Muy intenso** | 9–10 | Insoportable o el peor dolor imaginable | rojo oscuro / vino |

**Cómo se captura:** un deslizador EVA 0-10 **y** cinco botones de nivel; al mover uno se actualiza el otro. Guardar **siempre el número EVA** (dato clínico) y derivar la etiqueta. No usar solo color: mostrar el número dentro del marcador (accesibilidad y daltonismo).
Si prefieres solo 4 niveles como en tu ejemplo, usar: Leve (1-3) · Moderado (4-6) · Intenso (7-8) · Muy intenso (9-10), sin el nivel "Sin dolor" (una zona sin marcar equivale a "sin dolor referido").

## C.3 Catálogo de regiones tocables
Cada región tiene `id`, `vista` (anterior/posterior), `nombre` y `lateralidad` (**D** derecha, **I** izquierda, **C** central). Las regiones D/I se dibujan como dos zonas separadas. (Catálogo sugerido, ajustable por el equipo clínico.)

**Vista anterior**
| Zona | Regiones |
|---|---|
| Cabeza y cuello | cabeza/cara (C) · ATM (D, I) · cuello anterior (C) |
| Tronco | hombro anterior (D, I) · pecho/pectoral (D, I) · esternón (C) · abdomen superior (C) · abdomen inferior (C) · costillas laterales (D, I) · cadera/ingle (D, I) |
| Miembro superior | brazo/bíceps (D, I) · codo (D, I) · antebrazo (D, I) · muñeca (D, I) · mano (D, I) |
| Miembro inferior | muslo anterior (D, I) · rodilla (D, I) · pierna anterior/tibia (D, I) · tobillo (D, I) · pie/dorso (D, I) |

**Vista posterior**
| Zona | Regiones |
|---|---|
| Cabeza y cuello | occipital/cabeza posterior (C) · cervical posterior (C) |
| Tronco | trapecio (D, I) · hombro posterior/deltoides (D, I) · escápula (D, I) · dorsal alta (C) · dorsal media/baja (C) · lumbar (C, o D/I) · sacro (C) · articulación sacroilíaca (D, I) · glúteo (D, I) |
| Miembro superior | brazo/tríceps (D, I) · codo posterior (D, I) · antebrazo posterior (D, I) · mano posterior (D, I) |
| Miembro inferior | muslo posterior/isquiotibiales (D, I) · hueco poplíteo (D, I) · pantorrilla (D, I) · tobillo/talón (D, I) · planta del pie (D, I) |

Las regiones deben alinearse con los síntomas del 06 (cervicalgia, dorsalgia, lumbalgia, sacroilíaco/pélvico, hombros/brazos/manos, cadera/rodilla/pies) para poder **autosugerir** esos "Sí" al marcar zonas, sin repetir datos (la nota del Excel pide no repetir lo ya registrado).

## C.4 Panel al seleccionar una zona
Campos (alineados con el texto original del Excel: *región, lateralidad, irradiación, inicio, intensidad EVA y características*):

| Campo | Tipo | Valores / notas |
|---|---|---|
| Región | automático | `id` de la zona tocada |
| Lateralidad | automático | D / I / C |
| **Intensidad (EVA)** | entero 0-10 | **obligatorio**; deriva el nivel de la tabla C.2 |
| **Nivel** | derivado | Leve / Moderado / Intenso / Muy intenso |
| Tipo de dolor | selección múltiple | punzante · quemante/ardor · opresivo/pesadez · pulsátil · eléctrico/choque · sordo/profundo · calambre/espasmo · rigidez · hormigueo/adormecimiento (sugerido) |
| Irradiación | selección de regiones (opcional) | hacia qué otras zonas del mapa se extiende; o ninguna |
| Inicio | fecha o texto | "hace 3 semanas", fecha exacta |
| Patrón / horario | selección | constante · intermitente · mañana · tarde · noche (despierta) · al movimiento · en reposo (sugerido) |
| Comentario | texto largo | agravantes, alivio, lo que el paciente quiera añadir |

Botones: **Guardar zona**, **Eliminar zona**, **Cancelar**.

## C.5 Comportamiento de la pantalla
1. Estado inicial del módulo (selector tri-estado, como el resto del formulario): **No evaluado** · **Sin dolor referido** · **Con dolor** (al marcar la primera zona pasa solo a "Con dolor").
2. Dos figuras visibles a la vez (o pestañas Anterior/Posterior en pantallas pequeñas). Zona resaltada al pasar el cursor o al tocar; zonas marcadas coloreadas con su nivel y el número EVA.
3. **Lista resumen** debajo del mapa: una fila por zona (región · lado · EVA · nivel), ordenable por intensidad, con editar/eliminar. Debe existir también una forma de añadir zonas **desde la lista** (accesibilidad, sin ratón).
4. Resultado derivado: **"Zona de mayor dolor"** y **EVA máximo**; este valor alimenta el campo "Intensidad 0-10, zona, momento" de la sección 02 sin teclearlo de nuevo.
5. Si una zona marcada tiene patrón "noche (despierta)" o "en reposo", mostrar una **advertencia suave** para revisar la sección 06D (seguridad antes de manipulación).
6. El mapa marcado debe poder **imprimirse / exportarse a PDF** como imagen dentro de la historia clínica.

## C.6 Datos que se guardan (ejemplo JSON)
```json
{
  "mapa_dolor": {
    "estado": "con_dolor",
    "eva_maximo": 8,
    "zonas": [
      {
        "region_id": "post_lumbar_C",
        "vista": "posterior",
        "lateralidad": "C",
        "eva": 8,
        "nivel": "intenso",
        "tipos": ["punzante", "rigidez"],
        "irradiacion": ["post_glúteo_D", "post_muslo_D"],
        "inicio": "2026-09-15",
        "patron": ["al_movimiento", "noche"],
        "comentario": "Peor al levantar peso; mejora con calor."
      }
    ]
  }
}
```
`estado` ∈ `no_evaluado` | `sin_dolor` | `con_dolor`. `nivel` ∈ `leve` | `moderado` | `intenso` | `muy_intenso`.

## C.7 Criterios de aceptación
- Se puede marcar, editar y eliminar zonas en ambas vistas, con lateralidad correcta.
- No se puede guardar una zona sin EVA.
- El color y la etiqueta siempre coinciden con el EVA guardado.
- El módulo funciona con pantalla táctil y con teclado.
- El mapa se incluye en la impresión/PDF de la anamnesis.

---

# Instrucciones para la IA que desarrollará el sistema

> Te adjunto este documento, `anamnesis.xlsx` y la pestaña `Acu` de `NUEVO.xlsx`. Usa este documento como fuente de verdad sobre estructura y valores; los Excel sirven para confirmar textos exactos. Construye en español: (1) el formulario de anamnesis integral (Parte A) con los selectores como enums con estado "no evaluado"; (2) el módulo de acupuntura (Parte B); (3) el módulo interactivo de mapa de dolor (Parte C). Ignora las demás pestañas de `NUEVO.xlsx`. Marca como TODO lo que el documento indique como "inferido", "sugerido" o "a confirmar" y corrige en el sistema los defectos del Excel señalados (selector de 06A inutilizable, selector de signos vitales, erratas de los 6 niveles).

## Pendientes por confirmar con el equipo clínico
1. Qué se registra en cada celda de **pulso** en `Acu` (calidad, fuerza, escala).
2. Erratas de los **6 niveles** y correspondencia de las **4 capas**.
3. Contenido de las 3 celdas de cada bloque del **protocolo** (puntos, técnica, observaciones).
4. Catálogo final de **regiones del mapa de dolor** y escala de 5 vs 4 niveles.
5. Separar teléfono personal / emergencia y fecha de nacimiento / edad.
