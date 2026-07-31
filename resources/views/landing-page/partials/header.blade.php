<header class="header">
    <div class="header__brand">
        <img
            src="{{ asset('img/logo.png') }}"
            alt="Logo MARVAS"
            class=" h-32 w-auto"
        >
    </div>

    <nav
        class="nav"
        id="nav"
    >
        <ul class="nav__list">
            <li class="nav__item">
                <a
                    href="{{ route('home') }}"
                    class="{{ request()->routeIs('home') ? 'nav__link nav__link--active' : 'nav__link' }}"
                >
                    Inicio
                </a>
            </li>
            <li class="nav__item">
                <a
                    href="#nosotros"
                    class="nav__link"
                >
                    Nosotros
                </a>
            </li>
            <li class="nav__item">
                <a
                    href="#servicios"
                    class="{{ request()->routeIs('servicios.*') ? 'nav__link nav__link--active' : 'nav__link' }}"
                >
                    Servicios
                </a>
            </li>
            <li class="nav__item">
                <a
                    href="#contacto"
                    class="nav__link"
                >
                    Contacto
                </a>
            </li>
        </ul>
    </nav>

    <div>
        <flux:button
            icon="calendar"
            iconTrailing="chevron-right"
            class="btn--primary header__cta"
            href="#cita"
        >
            AGENDA TU CITA
        </flux:button>
        <flux:button
            icon="arrow-right-end-on-rectangle"
            class="btn--primary header__cta"
            :href="route('login')"
            wire:navigate
        />
    </div>

    <button
        type="button"
        class="header__toggle"
        id="navToggle"
        aria-label="Abrir menú"
        aria-expanded="false"
        aria-controls="nav"
    >
        <flux:icon
            name="menu"
            class="header__toggle-icon"
        />
    </button>
</header>
