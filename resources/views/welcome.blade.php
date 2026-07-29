<x-layouts::guest>
    <div class="container max-w-8xl mx-auto">
        <!-- ============================================ -->
        <!-- 1. HEADER / NAVBAR -->
        <!-- ============================================ -->
        <header class="header">
            <div class="header__brand">
                <img src="{{ asset('img/logo.png') }}" alt="Logo MARVAS" class=" h-32 w-auto">
            </div>

            <nav class="nav" id="nav">
                <ul class="nav__list">
                    <li class="nav__item">
                        <a href="#inicio" class="nav__link nav__link--active">
                            Inicio
                        </a>
                    </li>
                    <li class="nav__item">
                        <a href="#nosotros" class="nav__link">
                            Nosotros
                        </a>
                    </li>
                    <li class="nav__item">
                        <a href="#servicios" class="nav__link">
                            Servicios
                        </a>
                    </li>
                    <li class="nav__item">
                        <a href="#ciencia" class="nav__link">
                            Ciencia
                        </a>
                    </li>
                    <li class="nav__item">
                        <a href="#contacto" class="nav__link">
                            Contacto
                        </a>
                    </li>
                </ul>
            </nav>

            <div>
                <flux:button icon="calendar" iconTrailing="chevron-right" class="btn--primary header__cta"
                    href="#cita">
                    AGENDA TU CITA
                </flux:button>
                <flux:button icon="arrow-right-end-on-rectangle" class="btn--primary header__cta"
                    href="{{ route('login') }}" wire:navigate />
            </div>

            <button type="button" class="header__toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false"
                aria-controls="nav">
                <flux:icon name="menu" class="header__toggle-icon" />
            </button>
        </header>

        <!-- ============================================ -->
        <!-- 2. HERO SECTION -->
        <!-- ============================================ -->
        <section id="inicio" class="hero relative">
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
                    <flux:button iconTrailing="chevron-right" class="btn--primary" href="#servicios">
                        Conoce nuestros servicios
                    </flux:button>
                    <flux:button iconTrailing="chevron-right" variant="ghost" class="btn--gold" href="#servicios">
                        Agenda tu cita
                    </flux:button>
                </div>
            </div>

            <img src="{{ asset('img/adn.png') }}" alt="Doble hélice de ADN" class="hero__image">
        </section>

        <!-- ============================================ -->
        <!-- 3. MODELO + SERVICIOS -->
        <!-- ============================================ -->
        <section id="servicios" class="services">
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
                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon name="flask-conical" class="size-9" />
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

                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon name="leaf" class="size-9" />
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

                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon name="bone" class="size-9" />
                        </span>
                        <div>
                            <h3 class="service-card__title">Quiropraxia</h3>
                            <p class="service-card__text">
                                Alineación de la columna para mejorar tu movilidad, aliviar el dolor y
                                optimizar tu bienestar.
                            </p>
                        </div>
                    </article>

                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon name="flower-2" class="size-9" />
                        </span>
                        <div>
                            <h3 class="service-card__title">Masaje muscular</h3>
                            <p class="service-card__text">
                                Relaja, descontractura y mejora tu circulación para un cuerpo más
                                fuerte y ligero.
                            </p>
                        </div>
                    </article>

                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon name="pin" class="size-9" />
                        </span>
                        <div>
                            <h3 class="service-card__title">Acupuntura</h3>
                            <p class="service-card__text">
                                Equilibra tu energía, alivia el dolor y mejora tu bienestar físico y
                                emocional.
                            </p>
                        </div>
                    </article>

                    <article class="service-card">
                        <span class="service-card__icon">
                            <flux:icon name="eye" class="size-9" />
                        </span>
                        <div>
                            <h3 class="service-card__title">Iridología</h3>
                            <p class="service-card__text">
                                Análisis del iris para detectar desequilibrios y prevenir
                                enfermedades desde su origen.
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- ============================================ -->
        <!-- 4. FRANJA DE VALORES / DIFERENCIADORES -->
        <!-- ============================================ -->
        <section id="nosotros" class="values">
            <div class="value-card">
                <flux:icon name="dna" class="value-card__icon" />
                <div class="value-card__text">
                    <h3 class="value-card__title">Enfoque integral</h3>
                    <p class="value-card__desc">Abordamos tu salud desde cuerpo, mente y emoción.</p>
                </div>
            </div>

            <div class="value-card">
                <flux:icon name="microscope" class="value-card__icon" />
                <div class="value-card__text">
                    <h3 class="value-card__title">Ciencia e innovación</h3>
                    <p class="value-card__desc">Utilizamos tecnología avanzada y medicina biológica de vanguardia.</p>
                </div>
            </div>

            <div class="value-card">
                <flux:icon name="user-round" class="value-card__icon" />
                <div class="value-card__text">
                    <h3 class="value-card__title">Atención personalizada</h3>
                    <p class="value-card__desc">Planes diseñados especialmente para ti y tus necesidades únicas.</p>
                </div>
            </div>

            <div class="value-card">
                <flux:icon name="shield-check" class="value-card__icon" />
                <div class="value-card__text">
                    <h3 class="value-card__title">Resultados reales</h3>
                    <p class="value-card__desc">Mejoramos tu calidad de vida de manera sostenible.</p>
                </div>
            </div>
        </section>

        <!-- ============================================ -->
        <!-- 5. CTA FINAL -->
        <!-- ============================================ -->
        <section id="cita" class="cta">
            <div class="cta__heading">
                <p class="cta__eyebrow">
                    Tu transformación
                </p>
                <h2 class="cta__title">
                    Comienza hoy
                </h2>
            </div>

            <div class="cta__info">
                <flux:icon name="calendar-days" class="cta__info-icon" />
                <p class="cta__info-text">
                    Agenda tu evaluación personalizada y descubre tu mejor versión.
                </p>
            </div>

            <flux:button href="#cita" class="btn--gold" iconTrailing="chevron-right">
                AGENDA TU CITA AHORA
            </flux:button>
        </section>

        <!-- ============================================ -->
        <!-- 6. FOOTER -->
        <!-- ============================================ -->
        <footer class="footer">
            <div class="footer__brand">
                <img src="{{ asset('img/logo.png') }}" alt="Logo MARVAS" class="h-32 w-auto">
            </div>

            <div class="footer__social">
                <a href="#" aria-label="Instagram" class="footer__social-link">


                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="footer__social-icon">
                        <path
                            d="M224.3 141a115 115 0 1 0 -.6 230 115 115 0 1 0 .6-230zm-.6 40.4a74.6 74.6 0 1 1 .6 149.2 74.6 74.6 0 1 1 -.6-149.2zm93.4-45.1a26.8 26.8 0 1 1 53.6 0 26.8 26.8 0 1 1 -53.6 0zm129.7 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM399 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z" />

                    </svg>
                </a>
                <a href="#" aria-label="Facebook" class="footer__social-link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" class="footer__social-icon">
                        <path
                            d="M80 299.3l0 212.7 116 0 0-212.7 86.5 0 18-97.8-104.5 0 0-34.6c0-51.7 20.3-71.5 72.7-71.5 16.3 0 29.4 .4 37 1.2l0-88.7C291.4 4 256.4 0 236.2 0 129.3 0 80 50.5 80 159.4l0 42.1-66 0 0 97.8 66 0z" />
                    </svg>
                </a>
                <a href="#" aria-label="WhatsApp" class="footer__social-link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="footer__social-icon">
                        <path
                            d="M380.9 97.1c-41.9-42-97.7-65.1-157-65.1-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480 117.7 449.1c32.4 17.7 68.9 27 106.1 27l.1 0c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3 18.6-68.1-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zM325.1 300.5c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6z" />
                    </svg>
                </a>
            </div>
        </footer>
    </div>

</x-layouts::guest>
