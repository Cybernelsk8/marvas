<header
    class="header"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
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
        aria-label="Navegación principal"
        :class="{ 'nav--open': open }"
        @click="open = false"
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
                    href="/#servicios"
                    class="{{ request()->routeIs('servicios.*') ? 'nav__link nav__link--active' : 'nav__link' }}"
                >
                    Servicios
                </a>
            </li>
            <li class="nav__item">
                <a
                    href="/#nosotros"
                    class="nav__link"
                >
                    Nosotros
                </a>
            </li>
            <li class="nav__item">
                <a
                    href="{{ route('home') }}#contacto"
                    class="nav__link"
                >
                    Contacto
                </a>
            </li>
        </ul>

        <div class="nav__actions">
            <flux:button
                icon="calendar"
                iconTrailing="chevron-right"
                class="btn--primary w-full justify-center"
                href="{{ route('agendar-cita') }}"
            >
                SOLICITA TU CITA
            </flux:button>
            <flux:button
                icon="arrow-right-end-on-rectangle"
                class="btn--primary w-full justify-center"
                :href="route('login')"
                wire:navigate
            >
                INICIAR SESIÓN
            </flux:button>
        </div>
    </nav>

    <div>
        <flux:button
            icon="calendar"
            iconTrailing="chevron-right"
            class="btn--primary header__cta"
            href="{{ route('agendar-cita') }}"
        >
            SOLICITA TU CITA
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
        :aria-label="open ? 'Cerrar menú' : 'Abrir menú'"
        :aria-expanded="open.toString()"
        aria-controls="nav"
        @click="open = !open"
    >
        <flux:icon
            name="menu"
            class="header__toggle-icon"
            x-show="!open"
            x-cloak
        />
        <flux:icon
            name="x-mark"
            class="header__toggle-icon"
            x-show="open"
            x-cloak
        />
    </button>
</header>
