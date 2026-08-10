<x-layouts::landing-page>
    @include('landing-page.partials.servicio', [
        'title_hero' => 'QUIROPRAXIA',
        'subtitle_hero' => 'Terapia integrativa y personalizada',
        'description_hero' =>
            'Profesión de la salud enfocada en la evaluación y ajuste de la columna vertebral, articulaciones y sistema musculoesquelético.',
        'path_image_hero' => 'img/quiropraxia-hero.jpeg',
        'text_objetivo' => 'Favorecer la movilidad, postura y función corporal.',
        'icon_objetivo' => 'bone',
        'objetivos' => [
            'Favorecer una mejor movilidad.',
            'Mejorar la función corporal.',
            'Promover una mejor postura.',
            'Apoyar el funcionamiento del sistema musculoesquelético. ',
        ],
    ])
</x-layouts::landing-page>
