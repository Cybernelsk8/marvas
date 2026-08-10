<x-layouts::landing-page>

    @include('landing-page.partials.servicio', [
        'title_hero' => 'HOMEOPATÍA',
        'subtitle_hero' => 'Terapia integrativa y personalizada',
        'description_hero' =>
            'Sistema de medicina alternativa basado en el principio de “lo similar cura lo similar”, que emplea medicamentos preparados mediante diluciones específicas y seleccionados de forma individual para cada persona. Su enfoque integral considera no solo los síntomas físicos, sino también los aspectos emocionales, mentales y el estilo de vida del paciente.',
        'path_image_hero' => 'img/homeopatia-hero.jpeg',
        'text_objetivo' =>
            'Estimular la capacidad natural de autorregulación y recuperación del propio organismo. Su enfoque integral considera no solo los sintomas fisicos, sino tambien los aspectos emocionales, mentales y el estilo de vida del paciente.',
        'icon_objetivo' => 'flask-conical',
        'objetivos' => [
            'Favorece la capacidad natural de autorregulación y recuperación de tu organismo.',
            'Brinda un acompañamiento personalizado, de acuerdo con tus necesidades.',
            'Aborda tu bienestar de manera integral, considerando aspectos físicos, emocionales y mentales.',
            'Complementa tus procesos de recuperación respetando la respuesta individual de tu organismo.',
        ],
    ])
</x-layouts::landing-page>
