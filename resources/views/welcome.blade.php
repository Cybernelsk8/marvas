<x-layouts::guest>
    <header
        class="fixed top-0 w-full z-50 bg-surface/80 dark:bg-surface-dim/80 backdrop-blur-md shadow-sm border-b border-on-secondary-fixed-variant/10">
        <div class="flex justify-between items-center px-margin-mobile md:px-gutter max-w-container-max mx-auto h-20">
            <!-- Brand -->
            <div class="flex items-center gap-2">
                <a class="font-headline-md text-headline-md text-primary tracking-tight font-bold"
                    href="#">MARVAS</a>
                <span
                    class="hidden lg:block text-on-surface-variant font-label-md text-label-md border-l border-outline-variant pl-2 ml-2">Instituto
                    de Medicina Biológica Integral</span>
            </div>
            <!-- Desktop Nav -->
            <nav class="hidden md:flex gap-6 items-center">
                <a class="text-on-surface-variant font-label-md text-label-md hover:text-primary transition-colors duration-300"
                    href="#filosofia">Filosofía</a>
                <a class="text-on-surface-variant font-label-md text-label-md hover:text-primary transition-colors duration-300"
                    href="#modelo">Modelo 6+</a>
                <a class="text-on-surface-variant font-label-md text-label-md hover:text-primary transition-colors duration-300"
                    href="#ruta">Ruta de Recuperación</a>
                <a class="text-on-surface-variant font-label-md text-label-md hover:text-primary transition-colors duration-300"
                    href="#programas">Programas</a>
            </nav>
            <!-- Actions -->
            <div class="flex items-center gap-4">
                <a class="hidden md:inline-flex btn-primary text-on-primary px-6 py-3 rounded-full font-label-md text-label-md"
                    href="#contacto">
                    Agenda tu Evaluación
                </a>
                <button
                    class="md:hidden text-primary dark:text-primary-fixed-dim hover:text-primary transition-colors duration-300"
                    id="mobile-menu-btn">
                    <span class="material-symbols-outlined text-3xl">menu</span>
                </button>
            </div>
        </div>
    </header>
    <!-- NavigationDrawer (Mobile) -->
    <div class="fixed inset-y-0 left-0 z-[60] bg-surface dark:bg-surface-dim rounded-r-xl h-full w-80 shadow-xl transition-all duration-300 ease-in-out -translate-x-full border-r border-surface-container-low flex flex-col p-6 gap-4"
        id="mobile-menu">
        <div class="flex justify-between items-center mb-8">
            <span class="font-headline-sm text-headline-sm text-primary">MARVAS Medical</span>
            <button class="text-on-surface-variant hover:bg-surface-variant rounded-full p-2" id="close-menu-btn">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <nav class="flex flex-col gap-2 font-body-lg text-body-lg">
            <a class="mobile-link flex items-center gap-4 p-3 rounded-full text-on-surface-variant hover:bg-surface-variant transition-colors"
                href="#filosofia">
                <span class="material-symbols-outlined">biotech</span>
                Filosofía
            </a>
            <a class="mobile-link flex items-center gap-4 p-3 rounded-full text-on-surface-variant hover:bg-surface-variant transition-colors"
                href="#modelo">
                <span class="material-symbols-outlined">grid_view</span>
                Modelo 6+
            </a>
            <a class="mobile-link flex items-center gap-4 p-3 rounded-full text-on-surface-variant hover:bg-surface-variant transition-colors"
                href="#ruta">
                <span class="material-symbols-outlined">timeline</span>
                Ruta de Recuperación
            </a>
            <a class="mobile-link flex items-center gap-4 p-3 rounded-full text-on-surface-variant hover:bg-surface-variant transition-colors"
                href="#programas">
                <span class="material-symbols-outlined">payments</span>
                Programas
            </a>
        </nav>
    </div>
    <!-- Overlay for mobile menu -->
    <div class="fixed inset-0 bg-on-surface/20 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300"
        id="menu-overlay"></div>
    <main class="pt-20">
        <!-- Hero Section -->
        <section class="relative min-h-[795px] flex items-center pt-16 pb-section-padding-lg overflow-hidden">
            <div class="absolute inset-0 bg-surface-container-lowest z-0">
                <div class="bg-cover bg-center w-full h-full opacity-30 mix-blend-multiply"
                    data-alt="A macro shot of organic, flowing cellular structures and abstract DNA-like helices rendered in soft biological teals and warm gold tones. The lighting is sophisticated and medical, with a luminous, high-key cream background. The aesthetic is clean, modern, and high-end, representing biological integrity and advanced natural medicine. The composition is asymmetrical, leaving ample negative space for typography."
                    style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuDeLezzm1Ly_h2iB9nvW649H6CoMJjAoynM7tSwSY9sFGhzftnYb4zGnd_WmEWrxXoiGXZsJhrHA88lHeyNscQm6E2aJvI70NpaW8c4r5e1iZWzSMoevF1pU8pyLywk8lar7flnvksR1SnhnOZ4oaBYd_YFmyzPTaL9pEM0nc1WEK2G7jjfZ6leX3M-GV4MY1v4usei-66PrAJnceHsGkWkUyZT8hlIJly08YIJ7dt4MdwuLheetwpsy7KLFUxvxwDvdtilplfgYb0')">
                </div>
            </div>
            <div
                class="max-w-container-max mx-auto px-margin-mobile md:px-gutter relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-gutter items-center">
                <div class="lg:col-span-8 flex flex-col items-start gap-6">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2 bg-secondary-container/30 backdrop-blur-sm text-on-secondary-container rounded-full font-label-md text-label-md">
                        <flux:icon name="badge-check" />
                        MODELO 6+
                    </div>
                    <h1
                        class="font-display-lg-mobile text-display-lg-mobile md:font-display-lg md:text-display-lg text-on-surface max-w-3xl leading-tight">
                        Recupera tu energía. <br />
                        <span class="text-gradient font-bold">Vive tu mejor versión.</span>
                    </h1>
                    <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">
                        En MARVAS integramos medicina biológica y alternativa para abordar la raíz de tus padecimientos,
                        no solo los síntomas. Un enfoque holístico diseñado para devolverle el equilibrio natural a tu
                        cuerpo.
                    </p>
                    <div class="mt-4">
                        <a class="btn-primary text-on-primary px-8 py-4 rounded-full font-label-md text-label-md inline-flex items-center gap-2"
                            href="#contacto">
                            Agenda tu Evaluación Integral
                            <flux:icon name="arrow-right" />
                        </a>
                    </div>
                </div>
            </div>
        </section>
        <!-- Nuestra Filosofía -->
        <section class="py-section-padding-lg bg-surface relative" id="filosofia">
            <div
                class="max-w-container-max mx-auto px-margin-mobile md:px-gutter grid grid-cols-1 lg:grid-cols-2 gap-gutter items-center">
                <div
                    class="order-2 lg:order-1 relative rounded-3xl overflow-hidden aspect-square md:aspect-[4/3] card-shadow">
                    <img class="w-full h-full object-cover"
                        data-alt="A serene, high-end medical consultation room bathed in warm, natural sunlight. The decor features light woods, cream walls, and subtle biological teal accents. A sophisticated doctor in modern, minimalist attire is gently speaking with a relaxed patient. The atmosphere is nurturing, premium, and trustworthy, avoiding cold sterile hospital aesthetics. Soft focus background."
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuATrnp525l_bXbk9s9S413EHt3YNW05400u7C6PeVaSXcHq8jmiDEfV0vRbypyfdIP6VSdOB0P5IbSpQpBlOcCAjqozPEZKR7H2dJKy6zVdP4lGlWkLx2IVDnLXDNHEV2oCqx2Nnud38bzfu7ELQ4Vxtxjv3yi_X4X0ergFx3z37pq0IjAmWT0vcy9NzubFztwE-KfgkHKOpcN-eFq3YKHm8QmQMzfC_88WfuUdDOyQALT6lKGCYVXDvkMwiDmCcVMzwX7_BXPxd90" />
                </div>
                <div class="order-1 lg:order-2 flex flex-col gap-6 lg:pl-12">
                    <h2 class="font-headline-md text-headline-md text-primary">Nuestra Filosofía</h2>
                    <p class="font-body-lg text-body-lg text-on-surface-variant">
                        La salud no es simplemente la ausencia de enfermedad; es un estado de vitalidad biológica
                        integral. En MARVAS, creemos firmemente que el cuerpo tiene una capacidad innata para sanar
                        cuando se le proporcionan las herramientas y el entorno adecuados.
                    </p>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        A través de nuestro exclusivo <strong>Modelo 6+</strong>, nos dedicamos a identificar y tratar
                        las causas subyacentes de los desequilibrios orgánicos, superando el enfoque tradicional de
                        suprimir síntomas. Integramos ciencia médica avanzada con sabiduría natural para restaurar tu
                        bienestar fundamental.
                    </p>
                </div>
            </div>
        </section>
        <!-- Modelo 6+ / Especialidades -->
        <section class="py-section-padding-lg bg-surface-container-lowest relative" id="modelo">
            <div class="max-w-container-max mx-auto px-margin-mobile md:px-gutter relative z-10">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="font-headline-md text-headline-md text-primary mb-6">Modelo 6+ / Especialidades</h2>
                    <p class="font-body-lg text-body-lg text-on-surface-variant">Nuestro modelo integra siete
                        disciplinas fundamentales para abordar tu salud de manera integral, complementando la medicina
                        biológica con terapias efectivas.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        class="bg-surface p-8 rounded-2xl border border-surface-container-highest hover-card flex flex-col gap-4">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center text-primary mb-2">
                            <flux:icon name="droplets" />
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Homeopatía</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Estimula las defensas naturales del
                            cuerpo para recuperar el equilibrio interno.</p>
                    </div>
                    <div
                        class="bg-surface p-8 rounded-2xl border border-surface-container-highest hover-card flex flex-col gap-4">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center text-primary mb-2">
                            <flux:icon name="leaf" />
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Naturopatía</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Tratamientos basados en elementos
                            naturales para fomentar la autocuración.</p>
                    </div>
                    <div
                        class="bg-surface p-8 rounded-2xl border border-surface-container-highest hover-card flex flex-col gap-4">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center text-primary mb-2">
                            <span class="material-symbols-outlined">accessibility_new</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Quiropraxia</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Alineación del sistema
                            neuromusculoesquelético para optimizar la función corporal.</p>
                    </div>
                    <div
                        class="bg-surface p-8 rounded-2xl border border-surface-container-highest hover-card flex flex-col gap-4">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center text-primary mb-2">
                            <span class="material-symbols-outlined">sports_gymnastics</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Masaje Muscular</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Alivio de tensiones profundas y
                            mejora de la circulación para recuperación de tejidos.</p>
                    </div>
                    <div
                        class="bg-surface p-8 rounded-2xl border border-surface-container-highest hover-card flex flex-col gap-4">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center text-primary mb-2">
                            <span class="material-symbols-outlined">healing</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Acupuntura</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Regulación del flujo energético
                            para tratar dolor crónico y desequilibrios sistémicos.</p>
                    </div>
                    <div
                        class="bg-surface p-8 rounded-2xl border border-surface-container-highest hover-card flex flex-col gap-4">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center text-primary mb-2">
                            <span class="material-symbols-outlined">visibility</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Iridiología</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Análisis del iris para identificar
                            predisposiciones y el estado general de los órganos.</p>
                    </div>
                    <div
                        class="bg-surface p-8 rounded-2xl border border-surface-container-highest hover-card flex flex-col gap-4 md:col-span-2 lg:col-span-3 lg:w-1/3 lg:mx-auto">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center text-primary mb-2">
                            <span class="material-symbols-outlined">vaccines</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Sueroterapia Biológica</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Nutrición celular directa
                            intravenosa para desintoxicación y revitalización rápida.</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- ¿Por qué MARVAS? -->
        <section class="py-section-padding-lg bg-surface relative">
            <div class="max-w-container-max mx-auto px-margin-mobile md:px-gutter">
                <h2 class="font-headline-md text-headline-md text-primary text-center mb-12">¿Por qué MARVAS?</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="flex flex-col items-center text-center gap-4">
                        <div
                            class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-3xl">person</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Atención<br />Personalizada</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Cada paciente recibe un plan
                            adaptado a sus necesidades biológicas únicas.</p>
                    </div>
                    <div class="flex flex-col items-center text-center gap-4">
                        <div
                            class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-3xl">hub</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Modelo<br />6+</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Combinación sinérgica de terapias
                            para resultados superiores y duraderos.</p>
                    </div>
                    <div class="flex flex-col items-center text-center gap-4">
                        <div
                            class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-3xl">all_inclusive</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Enfoque<br />Integral</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Tratamos la raíz del problema, no
                            solo aliviamos los síntomas superficiales.</p>
                    </div>
                    <div class="flex flex-col items-center text-center gap-4">
                        <div
                            class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-3xl">medical_services</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Acompañamiento<br />Profesional
                        </h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">Guía constante por especialistas
                            altamente calificados en medicina biológica.</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- Tu Ruta de Recuperación -->
        <section class="py-section-padding-lg bg-surface-container-lowest relative" id="ruta">
            <div class="max-w-container-max mx-auto px-margin-mobile md:px-gutter">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="font-headline-md text-headline-md text-primary mb-6">Tu Ruta de Recuperación</h2>
                    <p class="font-body-lg text-body-lg text-on-surface-variant mb-4">Un proceso estructurado para
                        devolverte tu vitalidad, adaptado a una de nuestras tres rutas: <strong>Prevención, Atención, y
                            Optimización</strong>.</p>
                </div>
                <div class="relative max-w-4xl mx-auto">
                    <!-- Timeline Line -->
                    <div
                        class="hidden md:block absolute left-1/2 top-0 bottom-0 w-1 bg-surface-container-highest -translate-x-1/2 rounded-full">
                    </div>
                    <div class="flex flex-col gap-12">
                        <!-- Step 1 -->
                        <div class="relative flex flex-col md:flex-row items-center gap-8">
                            <div class="md:w-1/2 flex justify-end">
                                <div
                                    class="bg-surface p-6 rounded-2xl border border-surface-container-highest shadow-sm md:text-right w-full md:w-5/6">
                                    <span
                                        class="text-secondary font-bold text-sm uppercase tracking-wider mb-2 block">Paso
                                        1</span>
                                    <h3 class="font-headline-sm text-headline-sm text-primary mb-3">Evaluación Integral
                                    </h3>
                                    <p class="font-body-md text-body-md text-on-surface-variant">Análisis profundo de
                                        tu estado actual mediante iridiología, historial médico y diagnóstico biológico
                                        para trazar tu ruta ideal.</p>
                                </div>
                            </div>
                            <div
                                class="hidden md:flex absolute left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-primary border-4 border-surface-container-lowest items-center justify-center z-10">
                                <div class="w-2 h-2 bg-white rounded-full"></div>
                            </div>
                            <div class="md:w-1/2"></div>
                        </div>
                        <!-- Step 2 -->
                        <div class="relative flex flex-col md:flex-row items-center gap-8">
                            <div class="md:w-1/2"></div>
                            <div
                                class="hidden md:flex absolute left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-primary border-4 border-surface-container-lowest items-center justify-center z-10">
                                <div class="w-2 h-2 bg-white rounded-full"></div>
                            </div>
                            <div class="md:w-1/2 flex justify-start">
                                <div
                                    class="bg-surface p-6 rounded-2xl border border-surface-container-highest shadow-sm w-full md:w-5/6">
                                    <span
                                        class="text-secondary font-bold text-sm uppercase tracking-wider mb-2 block">Paso
                                        2</span>
                                    <h3 class="font-headline-sm text-headline-sm text-primary mb-3">Desinflamación</h3>
                                    <p class="font-body-md text-body-md text-on-surface-variant">Fase inicial crítica
                                        para reducir la inflamación sistémica, limpiar el organismo y preparar el
                                        terreno para la recuperación celular.</p>
                                </div>
                            </div>
                        </div>
                        <!-- Step 3 -->
                        <div class="relative flex flex-col md:flex-row items-center gap-8">
                            <div class="md:w-1/2 flex justify-end">
                                <div
                                    class="bg-surface p-6 rounded-2xl border border-surface-container-highest shadow-sm md:text-right w-full md:w-5/6">
                                    <span
                                        class="text-secondary font-bold text-sm uppercase tracking-wider mb-2 block">Paso
                                        3</span>
                                    <h3 class="font-headline-sm text-headline-sm text-primary mb-3">Programa
                                        Especializado</h3>
                                    <p class="font-body-md text-body-md text-on-surface-variant">Aplicación del Modelo
                                        6+ y tratamientos específicos enfocados en restaurar la función de tus sistemas
                                        y órganos afectados.</p>
                                </div>
                            </div>
                            <div
                                class="hidden md:flex absolute left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-primary border-4 border-surface-container-lowest items-center justify-center z-10">
                                <div class="w-2 h-2 bg-white rounded-full"></div>
                            </div>
                            <div class="md:w-1/2"></div>
                        </div>
                        <!-- Step 4 -->
                        <div class="relative flex flex-col md:flex-row items-center gap-8">
                            <div class="md:w-1/2"></div>
                            <div
                                class="hidden md:flex absolute left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-primary border-4 border-surface-container-lowest items-center justify-center z-10">
                                <div class="w-2 h-2 bg-white rounded-full"></div>
                            </div>
                            <div class="md:w-1/2 flex justify-start">
                                <div
                                    class="bg-surface p-6 rounded-2xl border border-surface-container-highest shadow-sm w-full md:w-5/6">
                                    <span
                                        class="text-secondary font-bold text-sm uppercase tracking-wider mb-2 block">Paso
                                        4</span>
                                    <h3 class="font-headline-sm text-headline-sm text-primary mb-3">Mantenimiento</h3>
                                    <p class="font-body-md text-body-md text-on-surface-variant">Consolidación de
                                        resultados y pautas de estilo de vida para asegurar que tu vitalidad se mantenga
                                        óptima a largo plazo.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Programas y Precios -->
        <section class="py-section-padding-lg bg-surface relative" id="programas">
            <div class="max-w-container-max mx-auto px-margin-mobile md:px-gutter">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="font-headline-md text-headline-md text-primary mb-6">Programas y Precios</h2>
                    <p class="font-body-lg text-body-lg text-on-surface-variant">Inversiones transparentes en tu salud
                        integral, diseñadas para brindarte el máximo valor y resultados duraderos.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                    <!-- Evaluación Integral -->
                    <div
                        class="bg-surface-container-lowest rounded-3xl p-8 border border-outline-variant/30 flex flex-col justify-between hover-card">
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">Evaluación Integral</h3>
                            <p class="text-on-surface-variant mb-6 text-sm">Punto de partida esencial</p>
                            <div class="text-4xl font-headline-md text-primary mb-6">Q.350</div>
                            <ul class="flex flex-col gap-3 text-on-surface-variant mb-8">
                                <li class="flex items-start gap-2"><span
                                        class="material-symbols-outlined text-secondary text-sm mt-1">check_circle</span>
                                    Diagnóstico por Iridiología</li>
                                <li class="flex items-start gap-2"><span
                                        class="material-symbols-outlined text-secondary text-sm mt-1">check_circle</span>
                                    Revisión de Historial Médico</li>
                                <li class="flex items-start gap-2"><span
                                        class="material-symbols-outlined text-secondary text-sm mt-1">check_circle</span>
                                    Plan de Ruta Personalizado</li>
                            </ul>
                        </div>
                        <a class="btn-primary text-center text-on-primary px-6 py-3 rounded-full font-label-md w-full"
                            href="#contacto">Agendar Ahora</a>
                    </div>
                    <!-- Desinflamación -->
                    <div
                        class="bg-primary-container text-on-primary-container rounded-3xl p-8 border-2 border-primary relative flex flex-col justify-between hover-card transform md:-translate-y-4">
                        <div
                            class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            Más Solicitado</div>
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-on-primary-container mb-2">Programa
                                Desinflamación</h3>
                            <p class="text-on-primary-container/80 mb-6 text-sm">Reseteo sistémico profundo</p>
                            <div class="text-4xl font-headline-md text-on-primary-container mb-6">Q.1,950</div>
                            <ul class="flex flex-col gap-3 text-on-primary-container/90 mb-8">
                                <li class="flex items-start gap-2"><span
                                        class="material-symbols-outlined text-secondary-container text-sm mt-1">check_circle</span>
                                    Sueroterapia Biológica</li>
                                <li class="flex items-start gap-2"><span
                                        class="material-symbols-outlined text-secondary-container text-sm mt-1">check_circle</span>
                                    Desintoxicación Celular</li>
                                <li class="flex items-start gap-2"><span
                                        class="material-symbols-outlined text-secondary-container text-sm mt-1">check_circle</span>
                                    Terapias Integrativas</li>
                            </ul>
                        </div>
                        <a class="bg-on-primary-container text-primary-container text-center px-6 py-3 rounded-full font-label-md w-full hover:bg-white transition-colors"
                            href="#contacto">Iniciar Programa</a>
                    </div>
                    <!-- Sueroterapia -->
                    <div
                        class="bg-surface-container-lowest rounded-3xl p-8 border border-outline-variant/30 flex flex-col justify-between hover-card">
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">Sueroterapia Biológica
                            </h3>
                            <p class="text-on-surface-variant mb-6 text-sm">Revitalización intravenosa</p>
                            <div class="text-4xl font-headline-md text-primary mb-6">Q.450 <span
                                    class="text-lg text-on-surface-variant font-body-md">/sesión</span></div>
                            <ul class="flex flex-col gap-3 text-on-surface-variant mb-8">
                                <li class="flex items-start gap-2"><span
                                        class="material-symbols-outlined text-secondary text-sm mt-1">check_circle</span>
                                    Fórmulas Personalizadas</li>
                                <li class="flex items-start gap-2"><span
                                        class="material-symbols-outlined text-secondary text-sm mt-1">check_circle</span>
                                    Absorción al 100%</li>
                                <li class="flex items-start gap-2"><span
                                        class="material-symbols-outlined text-secondary text-sm mt-1">check_circle</span>
                                    Resultados Inmediatos</li>
                            </ul>
                        </div>
                        <a class="btn-primary text-center text-on-primary px-6 py-3 rounded-full font-label-md w-full"
                            href="#contacto">Reservar Sesión</a>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                    <div class="bg-surface-container-low p-6 rounded-2xl flex flex-col justify-center">
                        <h4 class="font-headline-sm text-primary mb-2">Línea Neurofuncional</h4>
                        <p class="text-on-surface-variant mb-4 text-sm">Tratamientos especializados para equilibrio
                            nervioso y mental. Precios varían según el protocolo recomendado tras evaluación.</p>
                        <a class="text-secondary font-label-md flex items-center gap-1 hover:underline"
                            href="#contacto">Consultar información <span
                                class="material-symbols-outlined text-sm">chevron_right</span></a>
                    </div>
                    <div class="bg-surface-container-low p-6 rounded-2xl flex flex-col justify-center">
                        <h4 class="font-headline-sm text-primary mb-2">Recuperación Musculoesquelética</h4>
                        <p class="text-on-surface-variant mb-4 text-sm">Programas intensivos combinando quiropraxia,
                            masaje y acupuntura. Diseñados a medida para dolor crónico y rehabilitación.</p>
                        <a class="text-secondary font-label-md flex items-center gap-1 hover:underline"
                            href="#contacto">Consultar información <span
                                class="material-symbols-outlined text-sm">chevron_right</span></a>
                    </div>
                </div>
                <p class="text-xs text-on-surface-variant text-center max-w-2xl mx-auto">
                    *Aviso Legal: Los precios están sujetos a cambios sin previo aviso. Los resultados pueden variar de
                    un paciente a otro dependiendo del estado de salud individual y la adherencia a las recomendaciones
                    del Instituto MARVAS.
                </p>
            </div>
        </section>
        <!-- CTA Final -->
        <section class="py-section-padding-lg bg-primary relative overflow-hidden text-center" id="contacto">
            <div class="absolute inset-0 z-0">
                <div class="bg-cover bg-center w-full h-full opacity-10 mix-blend-overlay"
                    style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuDeLezzm1Ly_h2iB9nvW649H6CoMJjAoynM7tSwSY9sFGhzftnYb4zGnd_WmEWrxXoiGXZsJhrHA88lHeyNscQm6E2aJvI70NpaW8c4r5e1iZWzSMoevF1pU8pyLywk8lar7flnvksR1SnhnOZ4oaBYd_YFmyzPTaL9pEM0nc1WEK2G7jjfZ6leX3M-GV4MY1v4usei-66PrAJnceHsGkWkUyZT8hlIJly08YIJ7dt4MdwuLheetwpsy7KLFUxvxwDvdtilplfgYb0')">
                </div>
            </div>
            <div
                class="max-w-container-max mx-auto px-margin-mobile md:px-gutter relative z-10 flex flex-col items-center gap-8">
                <h2 class="font-display-lg text-4xl md:text-5xl text-on-primary max-w-2xl">¿Listo para Iniciar?</h2>
                <p class="font-body-lg text-on-primary/90 max-w-xl">
                    Da el primer paso hacia una salud plena y consciente. Contáctanos por WhatsApp para agendar tu
                    Evaluación Integral y resolver cualquier duda.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-6 mt-4">
                    <a class="bg-[#25D366] text-white px-8 py-4 rounded-full font-label-md text-lg inline-flex items-center gap-3 hover:bg-[#1EBE5A] transition-colors shadow-lg shadow-[#25D366]/20"
                        href="https://wa.me/50258623352">
                        <span class="material-symbols-outlined text-2xl">forum</span>
                        Escríbenos por WhatsApp
                    </a>
                    <div class="flex items-center gap-2 text-on-primary font-body-lg">
                        <span class="material-symbols-outlined">call</span>
                        <span class="font-bold tracking-wide">502-5862-3352</span>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <!-- Footer -->
    <footer
        class="bg-surface-container-highest dark:bg-inverse-surface w-full border-t border-outline-variant mt-section-padding-lg">
        <div
            class="grid grid-cols-1 md:grid-cols-4 gap-gutter px-margin-mobile md:px-gutter py-section-padding-sm max-w-container-max mx-auto">
            <div class="col-span-1 md:col-span-2 flex flex-col gap-4">
                <span class="font-headline-sm text-headline-sm text-primary font-bold tracking-tight">MARVAS</span>
                <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">Instituto de Medicina Biológica
                    Integral. Tu camino hacia una salud plena y consciente.</p>
            </div>
            <div class="flex flex-col gap-3 font-label-md text-label-md">
                <span class="text-on-surface font-bold mb-2">Enlaces</span>
                <a class="text-on-surface-variant hover:text-secondary transition-colors" href="#">Privacy
                    Policy</a>
                <a class="text-on-surface-variant hover:text-secondary transition-colors" href="#">Terms of
                    Service</a>
                <a class="text-on-surface-variant hover:text-secondary transition-colors" href="#">Medical
                    Disclaimer</a>
            </div>
            <div class="flex flex-col gap-3 font-label-md text-label-md">
                <span class="text-on-surface font-bold mb-2">Contacto</span>
                <a class="text-on-surface-variant hover:text-secondary transition-colors inline-flex items-center gap-2"
                    href="https://wa.me/50258623352">
                    <span class="material-symbols-outlined text-sm">chat</span>
                    502-5862-3352
                </a>
            </div>
        </div>
        <div class="text-center pb-6 font-label-md text-label-md text-on-surface-variant">
            © 2024 MARVAS Biological Integrity Clinic. All rights reserved.
        </div>
    </footer>
</x-layouts::guest>
