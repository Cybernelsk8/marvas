<x-layouts::landing-page>

    @include('landing-page.partials.servicio', [
        'title_hero' => 'ACUPUNTURA',
        'subtitle_hero' => 'Terapia integrativa y personalizada',
        'description_hero' =>
            'Disciplina terapéutica originaria de la Medicina Tradicional China, que utiliza agujas muy finas en puntos específicos del cuerpo.',
        'path_image_hero' => 'img/acupuntura-hero.jpeg',
        'text_objetivo' => 'Promover el equilibrio y la regulación funcional del organismo.',
        'icon_objetivo' => 'pin',
        'objetivos' => [
            'Favorecer el equilibrio del organismo.',
            'Apoyar la regulación funcional.',
            'Complementar los procesos de recuperación y bienestar dentro del abordaje integral.',
        ],
    ])



</x-layouts::landing-page>
