<?php

namespace Database\Seeders;

use App\Models\RegionCorporal;
use Illuminate\Database\Seeder;

class RegionesCorporalesSeeder extends Seeder
{
    /**
     * Regiones musculares del mapa corporal (recursos IA/mapa-corporal.png).
     * Para ampliar el catálogo basta agregar una fila aquí o desde la administración.
     */
    public function run(): void
    {
        $regiones = [
            'anterior' => [
                ['masetero', 'Masetero', true],
                ['esternocleidomastoideo', 'Esternocleidomastoideo', true],
                ['pectorales', 'Pectorales', true],
                ['deltoides_anterior', 'Deltoides', true],
                ['biceps', 'Bíceps', true],
                ['flexor_largo_dedos', 'Flexor largo de los dedos', true],
                ['abdominales', 'Abdominales', false],
                ['psoas', 'Psoas', true],
                ['aductores', 'Aductores', true],
                ['cuadriceps', 'Cuádriceps', true],
                ['tibial_anterior', 'Tibial anterior', true],
                ['peroneo_anterior', 'Peroneo anterior', true],
            ],
            'posterior' => [
                ['dorsales', 'Dorsales', true],
                ['trapecios', 'Trapecios', true],
                ['triceps', 'Tríceps', true],
                ['lumbar', 'Lumbar', false],
                ['extensor_largo_dedos', 'Extensor largo de los dedos', true],
                ['gluteos', 'Glúteos', true],
                ['isquiotibiales', 'Isquiotibiales', true],
                ['triceps_sural', 'Tríceps sural', true],
            ],
        ];

        foreach ($regiones as $vista => $items) {
            foreach ($items as $i => [$codigo, $nombre, $lateral]) {
                $region = RegionCorporal::firstOrNew(['codigo' => $codigo]);
                $region->fill([
                    'nombre' => $nombre,
                    'vista' => $vista,
                    'tiene_lateralidad' => $lateral,
                    'orden' => $i + 1,
                    'activo' => true,
                ]);

                // Los trazos corregidos desde el trazador no se sobrescriben al volver a sembrar.
                if (empty($region->trazos)) {
                    $region->trazos = self::trazoInicial($codigo, $vista, $lateral);
                }

                $region->save();
            }
        }
    }

    /**
     * Puntos aproximados (coordenadas de mapa-corporal.png) de la mitad izquierda de la imagen
     * o, para regiones centrales, del contorno completo. Se afinan con el trazador de regiones.
     */
    private const PUNTOS = [
        'masetero' => [[332, 142], [350, 148], [352, 175], [340, 190], [325, 178], [328, 155]],
        'esternocleidomastoideo' => [[345, 200], [357, 200], [380, 268], [368, 278], [350, 240]],
        'pectorales' => [[258, 350], [290, 300], [375, 290], [375, 395], [330, 390], [285, 380]],
        'deltoides_anterior' => [[232, 285], [262, 275], [290, 300], [258, 350], [222, 385], [210, 340]],
        'biceps' => [[222, 390], [258, 355], [272, 420], [240, 500], [205, 500], [212, 430]],
        'flexor_largo_dedos' => [[205, 505], [240, 505], [215, 600], [190, 650], [150, 640], [180, 560]],
        'abdominales' => [[318, 400], [432, 400], [432, 640], [375, 705], [318, 640]],
        'psoas' => [[285, 625], [320, 640], [358, 720], [340, 770], [300, 700]],
        'aductores' => [[340, 720], [372, 700], [372, 830], [350, 880], [335, 800]],
        'cuadriceps' => [[262, 690], [335, 780], [335, 900], [305, 960], [262, 940], [250, 800]],
        'tibial_anterior' => [[270, 1040], [300, 1040], [300, 1200], [272, 1240], [255, 1150]],
        'peroneo_anterior' => [[245, 1050], [270, 1040], [262, 1200], [238, 1200]],
        'trapecios' => [[785, 185], [748, 200], [690, 292], [730, 335], [785, 450], [770, 300]],
        'dorsales' => [[690, 300], [730, 340], [785, 460], [785, 540], [720, 520], [690, 420]],
        'triceps' => [[640, 310], [690, 300], [680, 420], [640, 540], [615, 500], [625, 380]],
        'lumbar' => [[715, 520], [862, 520], [840, 595], [736, 595]],
        'extensor_largo_dedos' => [[615, 505], [640, 545], [610, 650], [585, 650], [600, 560]],
        'gluteos' => [[695, 590], [788, 600], [788, 745], [720, 742], [690, 680]],
        'isquiotibiales' => [[690, 750], [785, 750], [780, 860], [735, 920], [700, 880]],
        'triceps_sural' => [[668, 930], [730, 930], [725, 1030], [700, 1100], [675, 1050]],
    ];

    /** Ejes de simetría de cada vista en la imagen. */
    private const EJE = ['anterior' => 375, 'posterior' => 788];

    private static function trazoInicial(string $codigo, string $vista, bool $lateral): ?array
    {
        $puntos = self::PUNTOS[$codigo] ?? null;
        if (! $puntos) {
            return null;
        }

        if (! $lateral) {
            return ['central' => self::path($puntos)];
        }

        $espejo = array_map(fn ($p) => [2 * self::EJE[$vista] - $p[0], $p[1]], $puntos);

        // Vista anterior: el paciente está de frente, su derecha queda a la izquierda de la imagen.
        // Vista posterior: está de espaldas, su derecha queda a la derecha de la imagen.
        return $vista === 'anterior'
            ? ['derecha' => self::path($puntos), 'izquierda' => self::path($espejo)]
            : ['derecha' => self::path($espejo), 'izquierda' => self::path($puntos)];
    }

    private static function path(array $puntos): string
    {
        $segmentos = array_map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').$p[0].' '.$p[1], $puntos, array_keys($puntos));

        return implode(' ', $segmentos).' Z';
    }
}
