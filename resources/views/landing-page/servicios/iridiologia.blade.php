<x-layouts::landing-page>

    @include('landing-page.partials.servicio', [
        'title_hero' => 'IRIDOLOGÍA',
        'subtitle_hero' => 'Terapia integrativa y personalizada',
        'description_hero' =>
            'Método de observación y análisis del iris, utilizado como complemento dentro de la evaluación integral.',
        'path_image_hero' => 'img/iridologia-hero.jpeg',
        'text_objetivo' =>
            'Aportar información que contribuya a la personalización del acompañamiento terapéutico.',
        'icon_objetivo' => 'eye',
        'objetivos' => [
            'Complementar la evaluación integral.',
            'Aportar información adicional para orientar el acompañamiento.',
            'Favorecer la personalización del abordaje terapéutico.',
        ],
    ])
</x-layouts::landing-page>
