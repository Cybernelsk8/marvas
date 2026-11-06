<section
    id="contacto"
    class="branches"
>
    <div class="services__heading">
        <span class="services__eyebrow">
            <span class="services__eyebrow-line"></span>
            Contacto
            <span class="services__eyebrow-line"></span>
        </span>
        <h2 class="services__title">
            {{ $this->sedes->count() === 1 ? 'Visítanos en nuestra sede' : 'Visítanos en nuestras sedes' }}
        </h2>
        <p class="branches__intro">
            Estamos para acompañarte. Acércate a nuestra sede o escríbenos por WhatsApp para coordinar tu cita.
        </p>
    </div>

    @if ($this->sedes->isEmpty())
        <div class="branches__empty">
            <span class="branch-card__icon">
                <flux:icon
                    name="map-pin"
                    class="size-6"
                />
            </span>

            <h3 class="branch-card__title">
                Próximamente publicaremos nuestras sedes
            </h3>

            <p class="branch-card__details">
                Mientras tanto, puedes escribirnos y con gusto te orientamos para agendar tu cita.
            </p>

            @if ($whatsappGeneral)
                <a
                    href="{{ $whatsappGeneral }}"
                    class="branch-card__action branch-card__action--whatsapp"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <flux:icon
                        name="chat-bubble-left-right"
                        class="size-4"
                    />
                    Escríbenos por WhatsApp
                </a>
            @endif
        </div>
    @else
        <div class="branches__grid">
            @foreach ($this->sedes as $sede)
                <article
                    class="branch-card"
                    wire:key="sede-{{ $sede['id'] }}"
                    x-data="{ mapaAbierto: false }"
                >
                    <div class="branch-card__heading">
                        <span class="branch-card__icon">
                            <flux:icon
                                name="map-pin"
                                class="size-6"
                            />
                        </span>
                        <h3 class="branch-card__title">
                            {{ $sede['nombre'] }}
                        </h3>
                    </div>

                    <address class="branch-card__details">
                        @if ($sede['direccion'])
                            <span class="branch-card__row">
                                <flux:icon
                                    name="map-pin"
                                    class="branch-card__row-icon"
                                />
                                <span>{{ $sede['direccion'] }}</span>
                            </span>
                        @endif

                        @if ($sede['telefono'])
                            <span class="branch-card__row">
                                <flux:icon
                                    name="phone"
                                    class="branch-card__row-icon"
                                />
                                @if ($sede['telefono_enlace'])
                                    <a
                                        href="{{ $sede['telefono_enlace'] }}"
                                        class="branch-card__link"
                                    >
                                        {{ $sede['telefono'] }}
                                    </a>
                                @else
                                    <span>{{ $sede['telefono'] }}</span>
                                @endif
                            </span>
                        @endif
                    </address>

                    @if ($sede['mapa'] || $sede['whatsapp'])
                        <div class="branch-card__actions">
                            @if ($sede['mapa'])
                                <button
                                    type="button"
                                    class="branch-card__action branch-card__action--primary"
                                    x-on:click="mapaAbierto = ! mapaAbierto"
                                    x-bind:aria-expanded="mapaAbierto"
                                    aria-controls="mapa-sede-{{ $sede['id'] }}"
                                >
                                    <flux:icon
                                        name="map"
                                        class="size-4"
                                    />
                                    <span x-text="mapaAbierto ? 'Ocultar mapa' : 'Ver mapa'">Ver mapa</span>
                                </button>

                                @if ($sede['como_llegar'])
                                    <a
                                        href="{{ $sede['como_llegar'] }}"
                                        class="branch-card__action"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <flux:icon
                                            name="arrow-top-right-on-square"
                                            class="size-4"
                                        />
                                        Cómo llegar
                                    </a>
                                @endif
                            @endif

                            @if ($sede['whatsapp'])
                                <a
                                    href="{{ $sede['whatsapp'] }}"
                                    class="branch-card__action branch-card__action--whatsapp"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <flux:icon
                                        name="chat-bubble-left-right"
                                        class="size-4"
                                    />
                                    WhatsApp
                                </a>
                            @endif
                        </div>
                    @endif

                    @if ($sede['mapa'])
                        <div
                            id="mapa-sede-{{ $sede['id'] }}"
                            class="branch-card__map"
                            x-show="mapaAbierto"
                            x-cloak
                        >
                            <template x-if="mapaAbierto">
                                <iframe
                                    x-bind:src="@js($sede['mapa'])"
                                    title="Ubicación de {{ $sede['nombre'] }} en Google Maps"
                                    class="branch-card__map-frame"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    allowfullscreen
                                ></iframe>
                            </template>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
