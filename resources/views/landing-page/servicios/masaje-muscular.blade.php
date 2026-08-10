<x-layouts::landing-page>
    @include('landing-page.partials.servicio', [
        'title_hero' => ' MASAJE MUSCULAR',
        'subtitle_hero' => 'Terapia integrativa y personalizada',
        'description_hero' => 'Disciplina terapéutica manual orientada al trabajo de músculos y tejidos blandos.',
        'path_image_hero' => 'img/masaje-muscular-hero.jpeg',
        'text_objetivo' =>
            'Aliviar tensiones musculares, favorecer la recuperación física y promover una mejor funcionalidad.',
        'icon_objetivo' => 'flower-2',
        'objetivos' => [
            'Aliviar tensiones musculares.',
            'Favorecer la recuperación física.',
            'Mejorar la funcionalidad muscular.',
            'Promover el bienestar de músculos y tejidos blandos. ',
        ],
    ])
</x-layouts::landing-page>
