<x-layouts::landing-page>
    @include('landing-page.partials.servicio', [
        'title_hero' => 'NATUROPATÍA',
        'subtitle_hero' => 'Terapia integrativa y personalizada',
        'description_hero' =>
            'Disciplina de medicina natural que promueve el bienestar mediante un enfoque holístico e integral de la persona. Emplea herramientas como la alimentación, fitoterapia, suplementación nutricional y otros recursos naturales.',
        'path_image_hero' => 'img/naturopatia-hero.jpeg',
        'text_objetivo' =>
            'Apoyar el bienestar y favorecer el adecuado funcionamiento del organismo mediante recursos naturales.',
        'icon_objetivo' => 'leaf',
        'objetivos' => [
            'Favorecer el bienestar integral.',
            'Apoyar el adecuado funcionamiento del organismo.',
            'Incorporar alimentación y hábitos saludables al proceso terapéutico.',
            'Utilizar fitoterapia, suplementación y otros recursos naturales de acuerdo con las necesidades de la persona. ',
        ],
    ])
</x-layouts::landing-page>
