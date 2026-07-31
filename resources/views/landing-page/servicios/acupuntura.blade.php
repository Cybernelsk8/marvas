<x-layouts::landing-page>

    @include('landing-page.partials.hero-servicios', [
        'title' => 'ACUPUNTURA',
        'description' =>
            'lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
        'path_image' => 'img/acupuntura-hero.png',
    ])

    @include('landing-page.partials.objetivos-servicios', [
        'text' => 'Es estimular la capacidad natural de autorregulación y recuperación del propio
                            organismo. Su enfoque integral considera no solo los síntomas físicos, sino también los aspectos
                            emocionales, mentales y el estilo de vida del paciente.',
        'icon' => 'pin',
    ])

    <section class="px-5 py-12 bg-[#F3ECE1] sm:px-10 md:px-16 md:py-20">
        <div class="services__heading">
            <span class="services__eyebrow">
                <span class="services__eyebrow-line"></span>
                beneficios
                <span class="services__eyebrow-line"></span>
            </span>
            <h2 class="services__title">
                Lo que la homeopatía puede hacer por tí
            </h2>
        </div>

        <div class="grid md:gap-9 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <article class="service-card">
                    <span class="service-card__icon">
                        <flux:icon
                            name="flask-conical"
                            class="size-9"
                        />
                    </span>
                    <div>
                        <h3 class="service-card__title">
                            Homeopatía
                        </h3>
                        <p class="service-card__text">
                            Estimula tu capacidad de sanación natural de forma segura y efectiva.
                        </p>
                    </div>
                </article>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <article class="service-card">
                    <span class="service-card__icon">
                        <flux:icon
                            name="flask-conical"
                            class="size-9"
                        />
                    </span>
                    <div>
                        <h3 class="service-card__title">
                            Homeopatía
                        </h3>
                        <p class="service-card__text">
                            Estimula tu capacidad de sanación natural de forma segura y efectiva.
                        </p>
                    </div>
                </article>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <article class="service-card">
                    <span class="service-card__icon">
                        <flux:icon
                            name="flask-conical"
                            class="size-9"
                        />
                    </span>
                    <div>
                        <h3 class="service-card__title">
                            Homeopatía
                        </h3>
                        <p class="service-card__text">
                            Estimula tu capacidad de sanación natural de forma segura y efectiva.
                        </p>
                    </div>
                </article>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <article class="service-card">
                    <span class="service-card__icon">
                        <flux:icon
                            name="flask-conical"
                            class="size-9"
                        />
                    </span>
                    <div>
                        <h3 class="service-card__title">
                            Homeopatía
                        </h3>
                        <p class="service-card__text">
                            Estimula tu capacidad de sanación natural de forma segura y efectiva.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>
</x-layouts::landing-page>
