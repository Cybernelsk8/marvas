{{-- resources/views/components/calendar.blade.php --}}
{{--
    Grid de calendario, responsive con container queries NATIVAS de CSS
    (@container) y sin modal propio: vive dentro de un componente Livewire y
    le entrega la fecha + eventos de ese día para que el padre construya su
    propio modal/panel a su gusto.

    Por qué @container nativo y no clases de Tailwind apiladas (@min-[]:...):
    Tailwind detecta las clases que va a generar escaneando el TEXTO del
    archivo .blade.php. Si armamos los nombres de clase concatenando strings
    en PHP (ej. "@min-[{$px}px]:text-sm"), el token completo nunca aparece
    literal en el archivo fuente, así que el build de Tailwind no lo genera
    y el estilo simplemente no existe en producción. Usando un <style> con
    @container nativo evitamos ese problema por completo: es CSS puro, sin
    depender de que el compilador "adivine" clases dinámicas.

    Cómo recibe datos hacia afuera (elige uno o combina ambos):
      1) wire:model (opcional, two-way): solo te da la fecha ISO seleccionada.
            <flux:calendar wire:model="fecha" :events="$eventos" />
      2) Evento Livewire (siempre se dispara al hacer click en un día):
            <flux:calendar :events="$eventos" event-name="dia-seleccionado" />

            #[On('dia-seleccionado')]
            public function onDiaSeleccionado($date, $events) { ... }

    Responsive:
      - `size`: 'sm' | 'md' | 'lg' | 'xl' | 'auto'. Con 'auto', el propio
        contenedor CSS (container-type: inline-size) decide el tier según su
        ancho real, sin ResizeObserver ni JS.
      - `min-size`: tier mínimo cuando size='auto' (piso de legibilidad). El
        grid nunca baja de ese ancho de celda; si el contenedor real es más
        angosto, aparece scroll horizontal en vez de encoger más.
      - Cada celda mantiene aspect-ratio (1/1 en sm/md, 4/5 en lg/xl para
        dejar espacio a la previsualización de eventos).

    Tareas / citas (prop `events`):
      - ['2026-08-01' => [['title' => '...', 'time' => '...', 'color' => '#3b82f6']]]
      - En lg/xl se ven hasta `max-preview` eventos dentro de la celda.
      - En sm/md solo aparece un punto indicador si el día tiene eventos.
      - Slot opcional `eventItem` para personalizar cómo se ve cada evento
        dentro de la celda (recibe la variable Alpine `event` en scope).
--}}
@props([
    'label' => null,
    'size' => 'auto', // sm | md | lg | xl | auto
    'minSize' => 'sm', // piso de tamaño cuando size = auto
    'maxPreview' => 3, // eventos visibles dentro de la celda (solo lg/xl)
    'events' => [], // ['2026-08-01' => [['title' => '...', 'time' => '...', 'color' => '#3b82f6']]]
    'weekStartsOn' => 1, // 0 = domingo, 1 = lunes
    'minDate' => null, // 'Y-m-d'
    'maxDate' => null, // 'Y-m-d'
    'disabledDates' => [],
    'disabledRanges' => [],
    'disabledDaysOfWeek' => [],
    'initialMonth' => null, // 'Y-m', si no se define arranca en el mes actual
    'eventName' => 'calendar-day-selected', // evento Livewire despachado al hacer click en un día
    'autoMaxWidth' => '42rem', // ancho máximo cuando size = auto
])

@php
    $tierOrder = ['sm', 'md', 'lg', 'xl'];
    $size = in_array($size, [...$tierOrder, 'auto']) ? $size : 'auto';
    $minSize = in_array($minSize, $tierOrder) ? $minSize : 'sm';
    $hasModel = (bool) $attributes->whereStartsWith('wire:model')->first();

    $monthNames = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
    ];
    $dayNamesShort = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    $orderedDayNames = collect(range(0, 6))->map(fn($i) => $dayNamesShort[($weekStartsOn + $i) % 7])->toArray();

    // Definición de los 4 tiers de tamaño: umbral de contenedor (px), ancho
    // mínimo de celda y tipografía/aspecto asociados a cada uno.
    $tierDefs = [
        'sm' => ['min' => 0,   'cellMin' => 34,  'cellFont' => '11px', 'dowFont' => '9px',  'headerFont' => '12px', 'previewFont' => '9px',  'aspect' => '1 / 1', 'preview' => false],
        'md' => ['min' => 300, 'cellMin' => 46,  'cellFont' => '12px', 'dowFont' => '10px', 'headerFont' => '14px', 'previewFont' => '9px',  'aspect' => '1 / 1', 'preview' => false],
        'lg' => ['min' => 440, 'cellMin' => 76,  'cellFont' => '14px', 'dowFont' => '12px', 'headerFont' => '16px', 'previewFont' => '10px', 'aspect' => '4 / 5', 'preview' => true],
        'xl' => ['min' => 640, 'cellMin' => 104, 'cellFont' => '16px', 'dowFont' => '14px', 'headerFont' => '18px', 'previewFont' => '12px', 'aspect' => '4 / 5', 'preview' => true],
    ];

    $floorIdx = array_search($minSize, $tierOrder);

    if ($size === 'auto') {
        $baseTier = $tierDefs[$minSize];
        $aboveTiers = array_slice($tierOrder, $floorIdx + 1); // tiers que reciben su propio bloque @container
        $isContainer = true;
        $boxWidthStyle = "width:100%; max-width:{$autoMaxWidth};";
    } else {
        $baseTier = $tierDefs[$size];
        $aboveTiers = [];
        $isContainer = false;
        $fixedWidth = ($baseTier['cellMin'] * 7) + 48; // 7 celdas + 6 gaps(4px) + padding del box (p-3 = 12px * 2)
        $boxWidthStyle = "max-width:{$fixedWidth}px;";
    }

    // Sufijo único por instancia para no chocar si hay varios calendarios en la misma vista.
    $scopeId = 'fxcal' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
@endphp

<div class="grid gap-2.5">
    @isset($label)
        <flux:label>{{ $label }}</flux:label>
    @endisset

    <style>
        .{{ $scopeId }}-box { {{ $isContainer ? 'container-type: inline-size;' : '' }} }
        .{{ $scopeId }}-grid { grid-template-columns: repeat(7, minmax({{ $baseTier['cellMin'] }}px, 1fr)); }
        .{{ $scopeId }}-cell-text { font-size: {{ $baseTier['cellFont'] }}; }
        .{{ $scopeId }}-dow-text { font-size: {{ $baseTier['dowFont'] }}; }
        .{{ $scopeId }}-header-text { font-size: {{ $baseTier['headerFont'] }}; }
        .{{ $scopeId }}-preview-text { font-size: {{ $baseTier['previewFont'] }}; }
        .{{ $scopeId }}-cell { aspect-ratio: {{ $baseTier['aspect'] }}; }
        .{{ $scopeId }}-preview { display: {{ $baseTier['preview'] ? 'flex' : 'none' }}; }
        .{{ $scopeId }}-dot { display: {{ $baseTier['preview'] ? 'none' : 'block' }}; }

        @foreach($aboveTiers as $t)
            @php($def = $tierDefs[$t])
            @container (min-width: {{ $def['min'] }}px) {
                .{{ $scopeId }}-grid { grid-template-columns: repeat(7, minmax({{ $def['cellMin'] }}px, 1fr)); }
                .{{ $scopeId }}-cell-text { font-size: {{ $def['cellFont'] }}; }
                .{{ $scopeId }}-dow-text { font-size: {{ $def['dowFont'] }}; }
                .{{ $scopeId }}-header-text { font-size: {{ $def['headerFont'] }}; }
                .{{ $scopeId }}-preview-text { font-size: {{ $def['previewFont'] }}; }
                .{{ $scopeId }}-cell { aspect-ratio: {{ $def['aspect'] }}; }
                .{{ $scopeId }}-preview { display: {{ $def['preview'] ? 'flex' : 'none' }}; }
                .{{ $scopeId }}-dot { display: {{ $def['preview'] ? 'none' : 'block' }}; }
            }
        @endforeach
    </style>

    {{-- Envoltorio: centra el calendario; el ancho real lo decide el propio CSS --}}
    <div class="w-full flex justify-center">
        <div
            class="{{ $scopeId }}-box rounded-xl border border-zinc-200 dark:border-white/10 shadow-xs bg-white dark:bg-zinc-800 md:p-3 overflow-x-auto"
            style="{{ $boxWidthStyle }}"
            x-data="{
                events: @js($events),
                weekStartsOn: @js((int) $weekStartsOn),
                minDate: @js($minDate),
                maxDate: @js($maxDate),
                disabledDates: @js($disabledDates),
                disabledRanges: @js($disabledRanges),
                disabledDaysOfWeek: @js($disabledDaysOfWeek),
                monthNames: @js($monthNames),
                dayNames: @js($orderedDayNames),
                maxPreview: @js((int) $maxPreview),

                @if($hasModel)
                selected: @entangle($attributes->wire('model')),
                @else
                selected: null,
                @endif

                viewYear: null,
                viewMonth: '0',
                yearInput: '',

                init() {
                    const initialMonth = @js($initialMonth);
                    let base = initialMonth ? this.parseISO(initialMonth + '-01') : this.today();
                    
                    if (this.isMonthOutOfBounds(base.y, base.m)) {
                        const today = this.today();
                        if (!this.isMonthOutOfBounds(today.y, today.m)) {
                            base = today;
                        } else {
                            if (this.minDate) {
                                const min = this.parseISO(this.minDate);
                                base = { y: min.y, m: min.m, d: 1 };
                            } else if (this.maxDate) {
                                const max = this.parseISO(this.maxDate);
                                base = { y: max.y, m: max.m, d: 1 };
                            } else {
                                base = today;
                            }
                        }
                    }
                    
                    // Asignar valores
                    this.viewYear = base.y;
                    this.viewMonth = base.m;
                    this.yearInput = String(base.y);
                },

                pad(n) { return String(n).padStart(2, '0'); },
                parseISO(iso) { const [y, m, d] = iso.split('-').map(Number); return { y, m, d }; },
                toISO(cell) { return `${cell.y}-${this.pad(cell.m)}-${this.pad(cell.d)}`; },
                today() { const t = new Date(); return { y: t.getFullYear(), m: t.getMonth() + 1, d: t.getDate() }; },
                isToday(cell) { if (!cell) return false; const t = this.today(); return cell.y === t.y && cell.m === t.m && cell.d === t.d; },
                daysInMonth(y, m) { return new Date(y, m, 0).getDate(); },

                get days() {
                    const total = this.daysInMonth(this.viewYear, this.viewMonth);
                    const firstDow = (new Date(this.viewYear, this.viewMonth - 1, 1).getDay() - this.weekStartsOn + 7) % 7;
                    const cells = [];
                    for (let i = 0; i < firstDow; i++) cells.push(null);
                    for (let d = 1; d <= total; d++) cells.push({ y: this.viewYear, m: this.viewMonth, d });
                    while (cells.length % 7 !== 0) cells.push(null);
                    return cells;
                },

                get yearBounds() {
                    const t = this.today();
                    const floor = this.minDate ? this.parseISO(this.minDate).y : t.y - 90;
                    const ceil = this.maxDate ? this.parseISO(this.maxDate).y : t.y + 10;
                    return { floor, ceil };
                },
                get yearOptions() {
                    const { floor, ceil } = this.yearBounds;
                    const arr = [];
                    for (let y = floor; y <= ceil; y++) arr.push(y);
                    return arr;
                },
                isMonthOutOfBounds(y, m) {
                    if (this.minDate) { const min = this.parseISO(this.minDate); if (y < min.y || (y === min.y && m < min.m)) return true; }
                    if (this.maxDate) { const max = this.parseISO(this.maxDate); if (y > max.y || (y === max.y && m > max.m)) return true; }
                    return false;
                },
                get canGoPrev() { let m = this.viewMonth - 1, y = this.viewYear; if (m < 1) { m = 12; y -= 1; } return !this.isMonthOutOfBounds(y, m); },
                get canGoNext() { let m = this.viewMonth + 1, y = this.viewYear; if (m > 12) { m = 1; y += 1; } return !this.isMonthOutOfBounds(y, m); },
                get canGoToday() { const t = this.today(); return !this.isMonthOutOfBounds(t.y, t.m); },
                prevMonth() { if (!this.canGoPrev) return; this.viewMonth -= 1; if (this.viewMonth < 1) { this.viewMonth = 12; this.viewYear -= 1; } this.yearInput = String(this.viewYear); },
                nextMonth() { if (!this.canGoNext) return; this.viewMonth += 1; if (this.viewMonth > 12) { this.viewMonth = 1; this.viewYear += 1; } this.yearInput = String(this.viewYear); },
                goToday() { if (!this.canGoToday) return; const t = this.today(); this.viewYear = t.y; this.viewMonth = t.m; this.yearInput = String(t.y); },
                setMonth(m) { m = Number(m); if (this.isMonthOutOfBounds(this.viewYear, m)) return; this.viewMonth = m; },
                applyYear() {
                    let y = parseInt(this.yearInput, 10);
                    if (isNaN(y)) { this.yearInput = String(this.viewYear); return; }
                    const { floor, ceil } = this.yearBounds;
                    y = Math.min(Math.max(y, floor), ceil);
                    if (this.isMonthOutOfBounds(y, this.viewMonth)) {
                        if (this.minDate && y === this.parseISO(this.minDate).y) this.viewMonth = this.parseISO(this.minDate).m;
                        else if (this.maxDate && y === this.parseISO(this.maxDate).y) this.viewMonth = this.parseISO(this.maxDate).m;
                    }
                    this.viewYear = y;
                    this.yearInput = String(y);
                },

                isDisabled(cell) {
                    if (!cell) return true;
                    const iso = this.toISO(cell);
                    if (this.minDate && iso < this.minDate) return true;
                    if (this.maxDate && iso > this.maxDate) return true;
                    if (this.disabledDates.includes(iso)) return true;
                    const dow = new Date(cell.y, cell.m - 1, cell.d).getDay();
                    if (this.disabledDaysOfWeek.includes(dow)) return true;
                    for (const [start, end] of this.disabledRanges) { if (iso >= start && iso <= end) return true; }
                    return false;
                },

                isSelectedDate(cell) { return cell && this.selected === this.toISO(cell); },
                dayEvents(cell) { if (!cell) return []; return this.events[this.toISO(cell)] || []; },

                selectDay(cell) {
                    if (this.isDisabled(cell)) return;
                    const iso = this.toISO(cell);
                    this.selected = iso;
                    this.$wire.dispatch(@js($eventName), { date: iso, events: this.dayEvents(cell) });
                },
            }"
        >
            {{-- Header: navegación + mes + año + Hoy --}}
            <div class="flex items-center justify-center gap-2 mb-3">
                <flux:button
                    x-bind:disabled="!canGoPrev"
                    @click="prevMonth()"
                    x-bind:class="canGoPrev ? 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-white/10 cursor-pointer' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'"
                    class="p-1 rounded-md transition-colors shrink-0"
                    aria-label="Mes anterior"
                    icon="chevron-left"
                    variant="ghost"
                    size="sm"
                />

                <div class="{{ $scopeId }}-header-text flex items-center gap-1">
                    <select
                        x-model="viewMonth"
                        @change="setMonth($event.target.value)"
                        class="bg-transparent border-none font-medium text-zinc-700 dark:text-zinc-200 focus:outline-none focus:ring-0 cursor-pointer"
                    >
                        <template x-for="(name, idx) in monthNames" :key="idx">
                            <option 
                                :value="idx + 1"
                                :selected="idx + 1 === viewMonth"
                                :disabled="isMonthOutOfBounds(viewYear, idx + 1)"
                                x-text="name"
                            />
                        </template>
                    </select>

                    <input
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        :list="$id('{{ $scopeId }}-year')"
                        x-model="yearInput"
                        @change="applyYear()"
                        @keydown.enter.prevent="applyYear()"
                        class="w-16 bg-transparent border-none p-0 font-medium text-zinc-700 dark:text-zinc-200 focus:outline-none focus:ring-0 text-center"
                    >
                    <datalist id="{{ $scopeId }}-year">
                        <template x-for="y in yearOptions" :key="y">
                            <option :value="y"></option>
                        </template>
                    </datalist>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <flux:button 
                        size="sm" 
                        variant="ghost" 
                        x-bind:disabled="!canGoToday" 
                        @click="goToday()">
                        Hoy
                    </flux:button>

                    <flux:button
                        x-bind:disabled="!canGoNext"
                        @click="nextMonth()"
                        x-bind:class="canGoNext ? 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-white/10 cursor-pointer' : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'"
                        class="p-1 rounded-md transition-colors shrink-0"
                        aria-label="Mes siguiente"
                        icon="chevron-right"
                        variant="ghost"
                        size="xs"
                    />
                </div>
            </div>

            {{-- Nombres de los días --}}
            <div class="{{ $scopeId }}-grid grid gap-x-1 gap-y-1 mb-1">
                <template x-for="dn in dayNames" :key="dn">
                    <div class="{{ $scopeId }}-dow-text text-center font-medium text-zinc-400 dark:text-zinc-500" x-text="dn"></div>
                </template>
            </div>

            {{-- Grid de días --}}
            <div class="{{ $scopeId }}-grid grid gap-x-1 gap-y-1">
                <template x-for="(cell, cIndex) in days" :key="cIndex">
                    <div>
                        <template x-if="cell === null">
                            <span></span>
                        </template>

                        <template x-if="cell !== null">
                            <button
                                type="button"
                                @click="selectDay(cell)"
                                :disabled="isDisabled(cell)"
                                :aria-selected="isSelectedDate(cell)"
                                :class="[
                                    isSelectedDate(cell)
                                        ? 'bg-zinc-800 text-white dark:bg-white dark:text-zinc-900 font-medium'
                                        : isDisabled(cell)
                                            ? 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'
                                            : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10 cursor-pointer',
                                    isToday(cell) && !isSelectedDate(cell) && !isDisabled(cell) ? 'ring-1 ring-inset ring-zinc-300 dark:ring-white/20' : '',
                                ]"
                                class="{{ $scopeId }}-cell relative w-full flex flex-col items-center justify-start pt-1 rounded-lg transition-colors select-none overflow-hidden"
                            >
                                <span class="{{ $scopeId }}-cell-text font-medium" x-text="cell.d"></span>

                                {{-- Previsualización de eventos (visible solo en tiers lg/xl vía CSS) --}}
                                <div class="{{ $scopeId }}-preview mt-0.5 w-full px-1 flex-col gap-0.5 overflow-hidden">
                                    <template x-for="(event, idx) in dayEvents(cell).slice(0, maxPreview)" :key="idx">
                                        @if($eventItem ?? false)
                                            {{ $eventItem }}
                                        @else
                                            <div
                                                class="{{ $scopeId }}-preview-text truncate rounded-sm border-l-2 border-zinc-300 dark:border-white/20 bg-zinc-50 dark:bg-white/5 pl-1 pr-0.5 leading-tight text-left"
                                                :style="event.color ? ('border-color:' + event.color) : ''"
                                                x-text="event.title"
                                            ></div>
                                        @endif
                                    </template>
                                    <template x-if="dayEvents(cell).length > maxPreview">
                                        <div class="{{ $scopeId }}-preview-text text-zinc-400 px-1 text-left" x-text="'+' + (dayEvents(cell).length - maxPreview) + ' más'"></div>
                                    </template>
                                </div>

                                {{-- Indicador simple para tiers compactos (visible solo en sm/md vía CSS) --}}
                                <template x-if="dayEvents(cell).length > 0">
                                    <span
                                        class="{{ $scopeId }}-dot absolute bottom-1 size-1 rounded-full"
                                        :class="isSelectedDate(cell) ? 'bg-white dark:bg-zinc-900' : 'bg-zinc-500 dark:bg-zinc-300'"
                                    ></span>
                                </template>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>