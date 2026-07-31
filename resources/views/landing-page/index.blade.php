<div>
    <!-- ============================================ -->
    <!-- 2. HERO SECTION -->
    <!-- ============================================ -->
    <section
        id="inicio"
        class="hero relative"
    >
        <div class="hero__content z-10">
            <span class="hero__eyebrow">
                Ciencia que transforma vidas
            </span>
            <h1 class="hero__title">
                Recupera tu
                <br>
                <em class="hero__title-highlight">
                    Energía
                </em>
            </h1>
            <h2 class="hero__subtitle">
                <span>Vive tu mejor versión</span>
                <span class="hero__subtitle-line"></span>
                <span class="hero__subtitle-dot"></span>
            </h2>
            <p class="hero__text">Medicina biológica integral, avanzada y personalizada para ayudarte a alcanzar tu
                máximo potencial de salud, bienestar y longevidad.</p>

            <div class="hero__actions">
                <flux:button
                    iconTrailing="chevron-right"
                    class="btn--primary"
                    href="#servicios"
                >
                    Conoce nuestros servicios
                </flux:button>
                <flux:button
                    iconTrailing="chevron-right"
                    variant="ghost"
                    class="btn--gold"
                    href="#servicios"
                >
                    Agenda tu cita
                </flux:button>
            </div>
        </div>

        <img
            src="{{ asset('img/adn.png') }}"
            alt="Doble hélice de ADN"
            class="hero__image"
        >
    </section>

    <!-- ============================================ -->
    <!-- 3. MODELO + SERVICIOS -->
    <!-- ============================================ -->
    <section
        id="servicios"
        class="services"
    >
        <div class="services__model">
            <span class="services__model-label">
                Modelo
            </span>
            <h2 class="services__model-number">
                6
                <sup class="services__model-plus">
                    +
                </sup>
            </h2>
            <p class="services__model-caption">
                Terapias integrativas
            </p>
            <span class="services__model-line"></span>
        </div>

        <div>
            <div class="services__heading">
                <span class="services__eyebrow">
                    <span class="services__eyebrow-line"></span>
                    Nuestros servicios
                    <span class="services__eyebrow-line"></span>
                </span>
                <h2 class="services__title">
                    Ciencia, naturaleza y bienestar en equilibrio para ti
                </h2>
            </div>

            <div class="services__grid">
                <a href="{{ route('servicios.homeopatia') }}">
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
                </a>

                <a href="#">
                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon
                                name="leaf"
                                class="size-9"
                            />
                        </span>
                        <div>
                            <h3 class="service-card__title">
                                Naturopatía</h3>
                            <p class="service-card__text">
                                Terapias naturales para restaurar el equilibrio, desintoxicar y
                                revitalizar tu cuerpo.
                            </p>
                        </div>
                    </article>
                </a>

                <a href="#">
                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon
                                name="bone"
                                class="size-9"
                            />
                        </span>
                        <div>
                            <h3 class="service-card__title">Quiropraxia</h3>
                            <p class="service-card__text">
                                Alineación de la columna para mejorar tu movilidad, aliviar el dolor y
                                optimizar tu bienestar.
                            </p>
                        </div>
                    </article>
                </a>

                <a href="#">
                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon
                                name="flower-2"
                                class="size-9"
                            />
                        </span>
                        <div>
                            <h3 class="service-card__title">Masaje muscular</h3>
                            <p class="service-card__text">
                                Relaja, descontractura y mejora tu circulación para un cuerpo más
                                fuerte y ligero.
                            </p>
                        </div>
                    </article>
                </a>

                <a href="#">
                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon
                                name="pin"
                                class="size-9"
                            />
                        </span>
                        <div>
                            <h3 class="service-card__title">Acupuntura</h3>
                            <p class="service-card__text">
                                Equilibra tu energía, alivia el dolor y mejora tu bienestar físico y
                                emocional.
                            </p>
                        </div>
                    </article>
                </a>

                <a href="#">
                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon
                                name="eye"
                                class="size-9"
                            />
                        </span>
                        <div>
                            <h3 class="service-card__title">Iridología</h3>
                            <p class="service-card__text">
                                Análisis del iris para detectar desequilibrios y prevenir
                                enfermedades desde su origen.
                            </p>
                        </div>
                    </article>
                </a>
            </div>
        </div>
    </section>

    <!-- ============================================ -->
    <!-- 4. FRANJA DE VALORES / DIFERENCIADORES -->
    <!-- ============================================ -->
    <section
        id="nosotros"
        class="values"
    >
        <div class="value-card">
            <flux:icon
                name="dna"
                class="value-card__icon"
            />
            <div class="value-card__text">
                <h3 class="value-card__title">Enfoque integral</h3>
                <p class="value-card__desc">Abordamos tu salud desde cuerpo, mente y emoción.</p>
            </div>
        </div>

        <div class="value-card">
            <flux:icon
                name="microscope"
                class="value-card__icon"
            />
            <div class="value-card__text">
                <h3 class="value-card__title">Ciencia e innovación</h3>
                <p class="value-card__desc">Utilizamos tecnología avanzada y medicina biológica de vanguardia.</p>
            </div>
        </div>

        <div class="value-card">
            <flux:icon
                name="user-round"
                class="value-card__icon"
            />
            <div class="value-card__text">
                <h3 class="value-card__title">Atención personalizada</h3>
                <p class="value-card__desc">Planes diseñados especialmente para ti y tus necesidades únicas.</p>
            </div>
        </div>

        <div class="value-card">
            <flux:icon
                name="shield-check"
                class="value-card__icon"
            />
            <div class="value-card__text">
                <h3 class="value-card__title">Resultados reales</h3>
                <p class="value-card__desc">Mejoramos tu calidad de vida de manera sostenible.</p>
            </div>
        </div>
    </section>

    <!-- ============================================ -->
    <!-- 5. CTA FINAL -->
    <!-- ============================================ -->
    <section
        id="cita"
        class="cta"
    >
        <div class="cta__heading">
            <p class="cta__eyebrow">
                Tu transformación
            </p>
            <h2 class="cta__title">
                Comienza hoy
            </h2>
        </div>

        <div class="cta__info">
            <flux:icon
                name="calendar-days"
                class="cta__info-icon"
            />
            <p class="cta__info-text">
                Agenda tu evaluación personalizada y descubre tu mejor versión.
            </p>
        </div>

        <flux:button
            href="#cita"
            class="btn--gold"
            iconTrailing="chevron-right"
        >
            AGENDA TU CITA AHORA
        </flux:button>
    </section>
</div>
