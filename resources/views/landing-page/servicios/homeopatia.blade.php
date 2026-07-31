<x-layouts::landing-page>
    <section
        id="inicio"
        class="hero relative"
    >
        <div class="hero__content z-10">
            <h1 class="hero__title">
                HOMEOPATÍA
            </h1>
            <h2 class="hero__subtitle">
                <span>Terapia integrativa y personalizada</span>
                <span class="hero__subtitle-line"></span>
                <span class="hero__subtitle-dot"></span>
            </h2>
            <p class="hero__text">
                Sistema de medicina alternativa basado en el principio de "lo similar cura lo similar",
                que emplea medicamentos preparados mediante diluciones específicas y seleccionados de
                forma individual para cada persona.
            </p>

            <div class="hero__actions">
                <flux:button
                    iconTrailing="chevron-right"
                    class="btn--primary"
                    href="/#servicios"
                >
                    Otro servicios
                </flux:button>
            </div>
        </div>

        <img
            src="{{ asset("img/adn.png") }}"
            alt="Doble hélice de ADN"
            class="hero__image"
        >
    </section>

    <section
        id="servicios"
        class="flex flex-col gap-12 px-5 py-7 bg-[#F7F1E8] sm:px-10 md:px-16 md:py-10 lg:grid lg:grid-cols-[1fr_3fr] lg:gap-16"
    >
        <div class="services__model">
            <span class="flex items-center justify-center size-48 rounded-full bg-cyan-950 text-[#C08A2E] shrink-0">
                <flux:icon
                    name="flask-conical"
                    class="size-36"
                />
            </span>
        </div>

        <div>
            <div class="services__heading flex flex-col items-center text-center justify-center">
                <span class="services__eyebrow">
                    <span class="services__eyebrow-line"></span>
                    objetivo
                    <span class="services__eyebrow-line"></span>
                </span>
                <p class="text-xl">
                    Es estimular la capacidad natural de autorregulación y recuperación del propio
                    organismo. Su enfoque integral considera no solo los síntomas físicos, sino también los aspectos
                    emocionales, mentales y el estilo de vida del paciente.
                </p>
            </div>
        </div>
    </section>

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
