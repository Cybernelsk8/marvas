<?php

namespace App\Support;

/**
 * Catálogos y colores del mapa de dolor
 * (recursos IA/MARVAS_especificacion_anamnesis_acupuntura_mapa_dolor.md, Parte C).
 */
class PainMapCatalog
{
    /** Tipos de dolor (selección múltiple). */
    public static function tipos(): array
    {
        return [
            'punzante' => 'Punzante',
            'quemante' => 'Quemante / ardor',
            'opresivo' => 'Opresivo / pesadez',
            'pulsatil' => 'Pulsátil',
            'electrico' => 'Eléctrico / choque',
            'sordo' => 'Sordo / profundo',
            'calambre' => 'Calambre / espasmo',
            'rigidez' => 'Rigidez',
            'hormigueo' => 'Hormigueo / adormecimiento',
        ];
    }

    /** Patrón u horario del dolor (selección múltiple). */
    public static function patrones(): array
    {
        return [
            'constante' => 'Constante',
            'intermitente' => 'Intermitente',
            'manana' => 'Mañana',
            'tarde' => 'Tarde',
            'noche' => 'Noche (despierta)',
            'movimiento' => 'Al movimiento',
            'reposo' => 'En reposo',
        ];
    }

    /** Etiquetas del nivel derivado del EVA. */
    public static function niveles(): array
    {
        return [
            'sin_dolor' => 'Sin dolor',
            'leve' => 'Leve',
            'moderado' => 'Moderado',
            'intenso' => 'Intenso',
            'muy_intenso' => 'Muy intenso',
        ];
    }

    /** Color del mapa de calor según EVA. Ej.: color(7) => '#ea580c'. */
    public static function color(?int $eva): string
    {
        return match (true) {
            $eva === null => '#a1a1aa',
            $eva <= 0 => '#86efac',
            $eva <= 3 => '#facc15',
            $eva <= 6 => '#fb923c',
            $eva <= 8 => '#ef4444',
            default => '#991b1b',
        };
    }

    /** Clave estable de una zona: región + lado. Ej.: key(3, 'derecha') => '3-derecha'. */
    public static function key(int $regionId, string $lado): string
    {
        return $regionId.'-'.$lado;
    }
}
