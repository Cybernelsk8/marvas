<section
    id="inicio"
    class="hero relative"
>
    <div class="hero__content z-10">
        <h1 class="hero__title">
            {{ $title }}
        </h1>
        <h2 class="hero__subtitle">
            <span>Terapia integrativa y personalizada</span>
            <span class="hero__subtitle-line"></span>
            <span class="hero__subtitle-dot"></span>
        </h2>
        <p class="hero__text">
            {{ $description }}
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
        src="{{ asset($path_image) }}"
        alt="Doble hélice de ADN"
        class="hero__image"
    >
</section>
