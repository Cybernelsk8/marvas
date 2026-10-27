<?php

namespace App\Support;

/**
 * Catálogos cerrados de la anamnesis integral
 * (recursos IA/MARVAS_especificacion_anamnesis_acupuntura_mapa_dolor.md, Parte A).
 *
 * Cada bloque devuelve [clave => [label, guia]]. Los valores guardados por ítem son
 * ['estado' => ..., 'detalle' => ...] para distinguir "se preguntó y es No" de "no se preguntó".
 */
class AnamnesisCatalog
{
    /** Estados de cada tipo de selector (TRI_SI_NO, SINTOMAS, EXPLORACION_A, EXPLORACION_B, INDICE_FUNCIONAL). */
    public const ESTADOS = [
        'tri' => ['no' => 'No', 'si' => 'Sí', 'no_evaluado' => 'No evaluado'],
        'sintomas' => ['sin_sintomas' => 'Sin síntomas', 'con_sintomas' => 'Con síntomas', 'no_evaluado' => 'No evaluado'],
        'exploracion_a' => ['normal' => 'Normal', 'alterado' => 'Alterado', 'no_evaluado' => 'No evaluado'],
        'exploracion_b' => ['normal' => 'Normal', 'alterado' => 'Alterado', 'no_realizado' => 'No realizado'],
        'indice' => ['bueno' => 'Bueno', 'ligero' => 'Ligero', 'moderado' => 'Moderado', 'malo' => 'Malo'],
    ];

    public const LATERALIDADES = ['derecha' => 'Derecha', 'izquierda' => 'Izquierda', 'bilateral' => 'Bilateral'];

    // Inciso 03: bloques con selector Sí/No/No evaluado y detalle.
    public static function antecedentes(): array
    {
        return [
            'enfermedades_cronicas' => ['Enfermedades crónicas y previas', 'Cuáles'],
            'cirugias' => ['Cirugías', 'Tipo de cirugía y año'],
            'hospitalizaciones' => ['Hospitalizaciones', 'Motivo y fecha'],
            'traumatismos_fracturas' => ['Traumatismos y fracturas', 'Región y fecha'],
            'alergias' => ['Alergias', 'Sustancia y reacción'],
            'medicamentos' => ['Medicamentos actuales', 'Nombre, dosis y frecuencia'],
            'implantes_protesis' => ['Implantes metálicos o prótesis', 'Tipo y localización'],
            'embarazo' => ['Embarazo posible (si aplica)', 'Semanas o fecha de última menstruación'],
        ];
    }

    // Inciso 04: 15 sistemas con selector Sin síntomas/Con síntomas/No evaluado y hallazgos.
    public static function sistemas(): array
    {
        return [
            'piel_anexos' => ['Piel y anexos', 'Erupciones, masas, prurito, sequedad, coloración, pelo, uñas, sudoración'],
            'cabeza_cuello' => ['Cabeza y cuello', 'Cefalea, heridas, masas, bocio, rigidez'],
            'ojos' => ['Ojos', 'Lentes, último examen, dolor, enrojecimiento, lagrimeo, diplopía, visión'],
            'oidos_equilibrio' => ['Oídos y equilibrio', 'Acúfenos, vértigo, otalgia, infecciones, secreciones'],
            'nariz_senos' => ['Nariz y senos', 'Resfriados, congestión, rinorrea, prurito, sinusitis'],
            'boca_garganta' => ['Boca y garganta', 'Dientes, encías, sangrado, dolor, disfagia, infecciones'],
            'mamas' => ['Mamas', 'Masas, dolor, secreción, autoexploración (si corresponde)'],
            'respiratorio' => ['Respiratorio', 'Tos, esputo, hemoptisis, sibilancias, asma, bronquitis, neumonía, disnea'],
            'cardiovascular' => ['Cardiovascular', 'HTA, soplos, dolor torácico, palpitaciones, edema, disnea'],
            'gastrointestinal' => ['Gastrointestinal', 'Ardor, náusea, vómito, hematemesis, digestión, estreñimiento, diarrea'],
            'oseo_muscular' => ['Óseo-muscular', 'Dolor, debilidad, rigidez, deformidad, limitación de movimiento'],
            'urinario' => ['Urinario', 'Frecuencia, poliuria, nicturia, disuria, hematuria, urgencia, chorro'],
            'reproductor_masculino' => ['Reproductor masculino', 'Hernias, secreción, lesiones, dolor o masa testicular, ITS (si corresponde)'],
            'reproductor_femenino' => ['Reproductor femenino', 'Menarquia, ciclos, sangrado, dolor, flujo, gestaciones (si corresponde)'],
            'neurologico' => ['Neurológico', 'Convulsiones, debilidad, parálisis, parestesias, temblor'],
        ];
    }

    // Inciso 06: 18 síntomas quiroprácticos (Sí/No/No evaluado + detalle).
    public static function sintomas(): array
    {
        return [
            'cefalea_recurrente' => ['Cefalea recurrente', 'Localización, frecuencia, asociación cervical'],
            'lentes_contacto' => ['Uso de lentes de contacto', 'Tiempo y motivo'],
            'alteraciones_equilibrio' => ['Alteraciones del equilibrio', 'Vértigo, inestabilidad, caídas'],
            'ortodoncia_protesis_dental' => ['Ortodoncia o prótesis dental', 'Tipo, fecha'],
            'atm_bruxismo' => ['Síntomas ATM o bruxismo', 'Dolor, chasquido, bloqueo, apertura'],
            'infecciones_garganta' => ['Infecciones de garganta recurrentes', 'Frecuencia, antecedentes'],
            'zumbido_oido' => ['Zumbido de oído', 'Lateralidad, inicio'],
            'paralisis_facial' => ['Parálisis facial previa', 'Lado, fecha, secuelas'],
            'dolor_ciatico' => ['Dolor ciático', 'Lado, trayecto, parestesias'],
            'adormecimiento_corporal' => ['Adormecimiento corporal', 'Zona, distribución, progresión'],
            'problemas_dermatologicos' => ['Problemas dermatológicos', 'Localización'],
            'cervicalgia' => ['Cervicalgia', 'Irradiación, rigidez, intensidad'],
            'dorsalgia' => ['Dorsalgia', 'Localización, respiración, postura'],
            'lumbalgia' => ['Lumbalgia', 'Irradiación, postura, carga'],
            'dolor_sacroiliaco_pelvico' => ['Dolor sacroilíaco o pélvico', 'Lateralidad, marcha'],
            'dolor_hombros_brazos_manos' => ['Dolor de hombros, brazos o manos', 'Lado, movimiento'],
            'dolor_cadera_rodilla_pies' => ['Dolor de cadera, rodilla o pies', 'Lado, marcha'],
            'dolor_nocturno_reposo' => ['Dolor nocturno o en reposo', 'Despertar, progresión'],
        ];
    }

    // Inciso 06A: postura, marcha y movilidad (Normal/Alterado/No evaluado + descripción).
    public static function posturaMovilidad(): array
    {
        return [
            'posicion_cabeza' => ['Posición de la cabeza', 'Anteriorización, inclinación o rotación'],
            'alineacion_hombros' => ['Alineación de hombros', 'Simetría, elevación D/I'],
            'curvaturas_vertebrales' => ['Curvaturas vertebrales', 'Cifosis, lordosis y región'],
            'alineacion_tronco' => ['Alineación del tronco', 'Asimetría o desviación'],
            'alineacion_pelvica' => ['Alineación pélvica', 'Simetría de crestas ilíacas'],
            'marcha_apoyo_plantar' => ['Marcha y apoyo plantar', 'Descripción'],
            'equilibrio' => ['Equilibrio', 'Prueba realizada y estabilidad'],
            'movilidad_cervical' => ['Movilidad cervical', 'Flexión, extensión, rotación, inclinación'],
            'movilidad_dorsal' => ['Movilidad dorsal', 'Rotación y extensión'],
            'movilidad_lumbar' => ['Movilidad lumbar', 'Flexión, extensión, inclinación'],
            'movilidad_extremidades' => ['Movilidad de extremidades', 'Articulación, lado, movimiento'],
            'palpacion_muscular' => ['Palpación muscular', 'Zona, sensibilidad, espasmo'],
        ];
    }

    // Inciso 06B: pruebas neurológicas y ortopédicas (Normal/Alterado/No realizado, lado y detalle; fuerza 0-5).
    public static function pruebas(): array
    {
        return [
            'fuerza_muscular' => ['Fuerza muscular segmentaria (0–5)', 'Segmento y resultado'],
            'sensibilidad_dermatomas' => ['Sensibilidad por dermatomas', 'Lado, resultado y detalle'],
            'reflejos_osteotendinosos' => ['Reflejos osteotendinosos', 'Lado, resultado y detalle'],
            'lasegue' => ['Elevación de pierna recta (Lasègue)', 'Lado, resultado y detalle'],
            'slump' => ['Prueba de Slump', 'Lado, resultado y detalle'],
            'spurling' => ['Spurling o provocación cervical', 'Lado, resultado y detalle'],
            'pruebas_sacroiliacas' => ['Pruebas sacroilíacas según indicación', 'Lado, resultado y detalle'],
            'pruebas_hombro_cadera' => ['Pruebas de hombro o cadera según indicación', 'Lado, resultado y detalle'],
        ];
    }

    // Inciso 06C: índice funcional (Bueno/Ligero/Moderado/Malo + comentario).
    public static function indiceFuncional(): array
    {
        return [
            'sueno' => ['Calidad del sueño', 'Afectación y comentarios'],
            'desplazamiento' => ['Desplazamiento', 'Distancia y tolerancia'],
            'trabajo' => ['Trabajo', 'Actividad y restricción'],
            'recreacion' => ['Recreación', 'Actividad y limitación'],
        ];
    }

    // Inciso 06D: 9 criterios de alarma (Sí/No/No evaluado).
    public static function seguridad(): array
    {
        return [
            'deficit_neurologico' => ['Déficit neurológico nuevo o progresivo', 'Detalle'],
            'esfinteres_silla_montar' => ['Alteración de esfínteres o anestesia en silla de montar', 'Detalle'],
            'trauma_fractura' => ['Trauma importante o sospecha de fractura', 'Detalle'],
            'fiebre_infeccion' => ['Fiebre o sospecha de infección espinal', 'Detalle'],
            'cancer_dolor_nuevo' => ['Cáncer conocido con dolor nuevo inexplicado', 'Detalle'],
            'osteoporosis_severa' => ['Osteoporosis severa o fragilidad ósea', 'Detalle'],
            'anticoagulacion_sangrado' => ['Anticoagulación o sangrado relevante', 'Detalle'],
            'alarma_vascular_cervical' => ['Síntomas de alarma vascular cervical', 'Detalle'],
            'cirugia_inestabilidad' => ['Cirugía reciente o inestabilidad conocida', 'Detalle'],
        ];
    }

    /**
     * Valores iniciales de un bloque. Ej.: defaults(self::sistemas(), 'no_evaluado')
     * => ['piel_anexos' => ['estado' => 'no_evaluado', 'detalle' => ''], ...]
     */
    public static function defaults(array $catalog, string $estado, array $extra = []): array
    {
        return array_map(
            fn (): array => ['estado' => $estado, 'detalle' => ''] + $extra,
            $catalog,
        );
    }

    /**
     * Combina lo guardado con los valores por defecto; ignora claves desconocidas.
     * Ej.: merge(self::sistemas(), 'no_evaluado', $anamnesis->revision_sistemas)
     */
    public static function merge(array $catalog, string $estado, ?array $stored, array $extra = []): array
    {
        $merged = self::defaults($catalog, $estado, $extra);

        foreach ($merged as $key => $item) {
            foreach (array_keys($item) as $field) {
                if (isset($stored[$key][$field])) {
                    $merged[$key][$field] = (string) $stored[$key][$field];
                }
            }
        }

        return $merged;
    }

    /** Reglas de validación de un bloque: estado dentro del catálogo y detalle de texto. */
    public static function rules(
        string $prefix,
        array $catalog,
        array $estados,
        array $extraRules = [],
        bool $allowBlank = false,
    ): array {
        $rules = [];

        foreach (array_keys($catalog) as $key) {
            $rules["{$prefix}.{$key}.estado"] = [$allowBlank ? 'nullable' : 'required', 'in:'.implode(',', $estados)];
            $rules["{$prefix}.{$key}.detalle"] = ['nullable', 'string', 'max:5000'];

            foreach ($extraRules as $field => $fieldRules) {
                $rules["{$prefix}.{$key}.{$field}"] = $fieldRules;
            }
        }

        return $rules;
    }

    /** Vacía los textos vacíos a null para guardarlos limpios. */
    public static function clean(array $block): array
    {
        foreach ($block as $key => $item) {
            foreach ($item as $field => $value) {
                $block[$key][$field] = $value === '' ? null : $value;
            }
        }

        return $block;
    }

    /** true si el bloque sigue con sus valores iniciales (nada capturado). */
    public static function isUntouched(array $block, string $estado): bool
    {
        foreach ($block as $item) {
            foreach ($item as $field => $value) {
                if ($field === 'estado' ? $value !== $estado : filled($value)) {
                    return false;
                }
            }
        }

        return true;
    }
}
