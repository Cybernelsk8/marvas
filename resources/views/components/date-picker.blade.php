{{-- resources/views/components/date-picker.blade.php --}}
@props([
    'label' => null,
    'placeholder' => 'Selecciona una fecha...',
    'mode' => 'single', // single | range | multiple
    'months' => 1, // 1 | 2 paneles visibles
    'format' => 'd/m/Y', // formato de salida visible (tokens: d j m n Y y)
    'size' => 'default',
    'invalid' => null,
    'minDate' => null, // 'Y-m-d'. Si no se define, el año arranca en el actual (no hacia atrás)
    'maxDate' => null, // 'Y-m-d'. Si no se define, el año llega hasta actual + 10
    'disabledDates' => [], // ['2026-08-01', ...]
    'disabledRanges' => [], // [['2026-08-05','2026-08-10'], ...]
    'disabledDaysOfWeek' => [], // [0,6] => domingo y sábado
    'clearable' => true,
    'weekStartsOn' => 1, // 0 = domingo, 1 = lunes
    'presets' => null, // null = auto (true si mode=range), o forzar true/false
])

@php
    $name = $attributes->whereStartsWith('wire:model')->first();
    $invalid ??= $name && $errors->has($name);

    $monthNames = [
        'Enero',
        'Febrero',
        'Marzo',
        'Abril',
        'Mayo',
        'Junio',
        'Julio',
        'Agosto',
        'Septiembre',
        'Octubre',
        'Noviembre',
        'Diciembre',
    ];

    $dayNamesShort = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    $orderedDayNames = collect(range(0, 6))->map(fn($i) => $dayNamesShort[($weekStartsOn + $i) % 7])->toArray();

    $months = max(1, min(2, (int) $months));
    $showPresets = $presets ?? $mode === 'range';

    $presetOptions = [
        ['key' => 'today', 'label' => 'Hoy'],
        ['key' => 'yesterday', 'label' => 'Ayer'],
        ['key' => 'last7', 'label' => 'Últimos 7 días'],
        ['key' => 'last30', 'label' => 'Últimos 30 días'],
        ['key' => 'thisMonth', 'label' => 'Este mes'],
        ['key' => 'lastMonth', 'label' => 'Mes pasado'],
    ];

    $classes = Flux::classes()
        ->add('relative flex items-center group w-full transition-all cursor-default')
        ->add(
            match ($size) {
                'sm' => 'h-8 py-1 px-2 text-sm rounded-md',
                'xs' => 'h-6 py-0.5 px-2 text-xs rounded-md',
                default => 'h-10 py-1.5 px-3 text-base sm:text-sm rounded-lg',
            },
        )
        ->add('bg-white dark:bg-zinc-700 border shadow-xs')
        ->add(
            $invalid
                ? 'border-red-500 ring-1 ring-red-500/20'
                : 'border-zinc-200 border-b-zinc-300/80 dark:border-white/10',
        )
        ->add(
            'focus-within:ring-2 focus-within:ring-zinc-200 focus-within:border-zinc-500 dark:focus-within:border-white/10',
        );

    $labelAttributes = Flux::attributesAfter('label:', $attributes);
@endphp

<div class="grid gap-2.5">
    @isset($label)
        <flux:label :attributes="$labelAttributes">{{ $label }}</flux:label>
    @endisset

    <div
        x-data="{
            open: false,
            selected: @entangle($attributes->wire('model')),
            mode: @js($mode),
            panelsCount: @js($months),
            weekStartsOn: @js((int) $weekStartsOn),
            format: @js($format),
            minDate: @js($minDate),
            maxDate: @js($maxDate),
            disabledDates: @js($disabledDates),
            disabledRanges: @js($disabledRanges),
            disabledDaysOfWeek: @js($disabledDaysOfWeek),
            monthNames: @js($monthNames),
            dayNames: @js($orderedDayNames),
            presetOptions: @js($presetOptions),
            viewYear: null,
            viewMonth: null,
            hoverDate: null,
            focusedDate: null,
            typedText: '',
            typedInvalid: false,
        
            init() {
                if (this.mode === 'single' && this.selected) {
                    this.typedText = this.formatDate(this.parseISO(this.selected));
                }
                // Mantiene el input sincronizado si la fecha cambia por click en el grid
                // o si el modelo cambia externamente (ej. wire:model desde el padre).
                this.$watch('selected', (val) => {
                    if (this.mode !== 'single') return;
                    this.typedText = val ? this.formatDate(this.parseISO(val)) : '';
                    this.typedInvalid = false;
                });
            },
        
            pad(n) { return String(n).padStart(2, '0'); },
        
            parseISO(iso) {
                if (!iso) return null;
                const [y, m, d] = iso.split('-').map(Number);
                return { y, m, d };
            },
        
            toISO(cell) { return `${cell.y}-${this.pad(cell.m)}-${this.pad(cell.d)}`; },
        
            today() {
                const t = new Date();
                return { y: t.getFullYear(), m: t.getMonth() + 1, d: t.getDate() };
            },
        
            isToday(cell) {
                if (!cell) return false;
                const t = this.today();
                return cell.y === t.y && cell.m === t.m && cell.d === t.d;
            },
        
            formatDate(cell) {
                if (!cell) return '';
                const map = {
                    d: this.pad(cell.d),
                    j: String(cell.d),
                    m: this.pad(cell.m),
                    n: String(cell.m),
                    Y: String(cell.y),
                    y: String(cell.y).slice(-2),
                };
                return this.format.replace(/[djmnYy]/g, c => map[c] ?? c);
            },
        
            // Traduce el prop format (ej. d/m/Y) a un regex con grupos de captura,
            // preservando cualquier separador literal (/, -, espacio, etc).
            buildFormatRegex() {
                let pattern = '';
                const order = [];
                for (const ch of this.format) {
                    if ('djmnYy'.includes(ch)) {
                        order.push(ch);
                        pattern += ch === 'Y' ? '(\\d{4})' : ch === 'y' ? '(\\d{2})' : '(\\d{1,2})';
                    } else {
                        pattern += ch.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    }
                }
                return { regex: new RegExp('^' + pattern + '$'), order };
            },
        
            // Convierte el texto escrito a {y,m,d} validando que sea una fecha real
            // (ej. 31/02 no pasa aunque el formato coincida).
            parseTyped(text) {
                const { regex, order } = this.buildFormatRegex();
                const match = text.trim().match(regex);
                if (!match) return null;
        
                const parts = {};
                order.forEach((token, i) => { parts[token] = parseInt(match[i + 1], 10); });
        
                const y = parts.Y ?? (parts.y !== undefined ? 2000 + parts.y : null);
                const m = parts.m ?? parts.n;
                const d = parts.d ?? parts.j;
                if (!y || !m || !d) return null;
        
                const dt = new Date(y, m - 1, d);
                if (dt.getFullYear() !== y || dt.getMonth() !== m - 1 || dt.getDate() !== d) return null;
        
                return { y, m, d };
            },
        
            handleTypedInput(value) {
                this.typedText = value;
        
                if (value.trim() === '') {
                    this.typedInvalid = false;
                    this.selected = null;
                    return;
                }
        
                const cell = this.parseTyped(value);
                if (!cell || this.isMonthOutOfBounds(cell.y, cell.m) || this.isDisabled(cell)) {
                    this.typedInvalid = true;
                    return;
                }
        
                this.typedInvalid = false;
                const iso = this.toISO(cell);
                this.selected = iso;
                this.focusedDate = iso;
                this.viewYear = cell.y;
                this.viewMonth = cell.m;
            },
        
            daysInMonth(y, m) { return new Date(y, m, 0).getDate(); },
        
            generatePanel(y, m) {
                const total = this.daysInMonth(y, m);
                const firstDow = (new Date(y, m - 1, 1).getDay() - this.weekStartsOn + 7) % 7;
                const cells = [];
                for (let i = 0; i < firstDow; i++) cells.push(null);
                for (let d = 1; d <= total; d++) cells.push({ y, m, d });
                return cells;
            },
        
            get panels() {
                const arr = [];
                for (let i = 0; i < this.panelsCount; i++) {
                    let m = this.viewMonth + i;
                    let y = this.viewYear;
                    while (m > 12) {
                        m -= 12;
                        y += 1;
                    }
                    arr.push({ year: y, month: m, days: this.generatePanel(y, m) });
                }
                return arr;
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
                if (this.minDate) {
                    const min = this.parseISO(this.minDate);
                    if (y < min.y || (y === min.y && m < min.m)) return true;
                }
                if (this.maxDate) {
                    const max = this.parseISO(this.maxDate);
                    if (y > max.y || (y === max.y && m > max.m)) return true;
                }
                const { floor, ceil } = this.yearBounds;
                if (y < floor || y > ceil) return true;
                return false;
            },
        
            get canGoPrev() {
                let m = this.viewMonth - 1,
                    y = this.viewYear;
                if (m < 1) {
                    m = 12;
                    y -= 1;
                }
                return !this.isMonthOutOfBounds(y, m);
            },
        
            get canGoNext() {
                let m = this.viewMonth + 1,
                    y = this.viewYear;
                if (m > 12) {
                    m = 1;
                    y += 1;
                }
                return !this.isMonthOutOfBounds(y, m);
            },
        
            prevMonth() {
                if (!this.canGoPrev) return;
                this.viewMonth -= 1;
                if (this.viewMonth < 1) {
                    this.viewMonth = 12;
                    this.viewYear -= 1;
                }
            },
        
            nextMonth() {
                if (!this.canGoNext) return;
                this.viewMonth += 1;
                if (this.viewMonth > 12) {
                    this.viewMonth = 1;
                    this.viewYear += 1;
                }
            },
        
            setYear(y) {
                y = Number(y);
                this.viewYear = Math.min(Math.max(y, this.yearBounds.floor), this.yearBounds.ceil);
            },
        
            setMonth(m) {
                m = Number(m);
                if (this.isMonthOutOfBounds(this.viewYear, m)) return;
                this.viewMonth = m;
            },
        
            isDisabled(cell) {
                if (!cell) return true;
                const iso = this.toISO(cell);
                if (this.minDate && iso < this.minDate) return true;
                if (this.maxDate && iso > this.maxDate) return true;
                if (this.disabledDates.includes(iso)) return true;
                const dow = new Date(cell.y, cell.m - 1, cell.d).getDay();
                if (this.disabledDaysOfWeek.includes(dow)) return true;
                for (const [start, end] of this.disabledRanges) {
                    if (iso >= start && iso <= end) return true;
                }
                return false;
            },
        
            isSelected(cell) {
                if (!cell) return false;
                const iso = this.toISO(cell);
                if (this.mode === 'single') return this.selected === iso;
                if (this.mode === 'multiple') return Array.isArray(this.selected) && this.selected.includes(iso);
                if (this.mode === 'range') return this.selected?.start === iso || this.selected?.end === iso;
                return false;
            },
        
            isInRange(cell) {
                if (this.mode !== 'range' || !cell) return false;
                const iso = this.toISO(cell);
                const sel = this.selected || {};
                const rangeEnd = sel.end || this.hoverDate;
                if (!sel.start || !rangeEnd) return false;
                const [a, b] = sel.start <= rangeEnd ? [sel.start, rangeEnd] : [rangeEnd, sel.start];
                return iso > a && iso < b;
            },
        
            isFocused(cell) {
                return cell && this.focusedDate === this.toISO(cell);
            },
        
            selectDate(cell) {
                if (this.isDisabled(cell)) return;
                const iso = this.toISO(cell);
                this.focusedDate = iso;
        
                if (this.mode === 'single') {
                    this.selected = iso;
                    this.open = false;
                    return;
                }
        
                if (this.mode === 'multiple') {
                    const sel = Array.isArray(this.selected) ? [...this.selected] : [];
                    const idx = sel.indexOf(iso);
                    this.selected = idx >= 0 ? sel.filter(v => v !== iso) : [...sel, iso].sort();
                    return;
                }
        
                if (this.mode === 'range') {
                    const sel = (this.selected && typeof this.selected === 'object') ? { ...this.selected } : { start: null, end: null };
                    if (!sel.start || (sel.start && sel.end)) {
                        this.selected = { start: iso, end: null };
                    } else if (iso < sel.start) {
                        this.selected = { start: iso, end: sel.start };
                        this.open = false;
                    } else {
                        this.selected = { start: sel.start, end: iso };
                        this.open = false;
                    }
                }
            },
        
            applyPreset(key) {
                const t = this.today();
                const base = new Date(t.y, t.m - 1, t.d);
                const fmt = (dt) => `${dt.getFullYear()}-${this.pad(dt.getMonth() + 1)}-${this.pad(dt.getDate())}`;
                let start, end;
        
                switch (key) {
                    case 'today':
                        start = end = fmt(base);
                        break;
                    case 'yesterday':
                        start = end = fmt(new Date(base.getTime() - 86400000));
                        break;
                    case 'last7':
                        start = fmt(new Date(base.getTime() - 6 * 86400000));
                        end = fmt(base);
                        break;
                    case 'last30':
                        start = fmt(new Date(base.getTime() - 29 * 86400000));
                        end = fmt(base);
                        break;
                    case 'thisMonth':
                        start = fmt(new Date(t.y, t.m - 1, 1));
                        end = fmt(new Date(t.y, t.m, 0));
                        break;
                    case 'lastMonth':
                        start = fmt(new Date(t.y, t.m - 2, 1));
                        end = fmt(new Date(t.y, t.m - 1, 0));
                        break;
                }
        
                if (this.minDate && start < this.minDate) start = this.minDate;
                if (this.maxDate && end > this.maxDate) end = this.maxDate;
        
                this.selected = { start, end };
                const p = this.parseISO(start);
                this.viewYear = p.y;
                this.viewMonth = p.m;
                this.open = false;
            },
        
            get displayLabel() {
                if (this.mode === 'single') {
                    return this.selected ? this.formatDate(this.parseISO(this.selected)) : null;
                }
                if (this.mode === 'multiple') {
                    const count = Array.isArray(this.selected) ? this.selected.length : 0;
                    if (count === 0) return null;
                    if (count === 1) return this.formatDate(this.parseISO(this.selected[0]));
                    return count + ' fechas seleccionadas';
                }
                if (this.mode === 'range') {
                    const sel = this.selected || {};
                    if (!sel.start) return null;
                    if (!sel.end) return this.formatDate(this.parseISO(sel.start)) + ' → …';
                    return this.formatDate(this.parseISO(sel.start)) + ' → ' + this.formatDate(this.parseISO(sel.end));
                }
                return null;
            },
        
            get hasValue() {
                if (this.mode === 'multiple') return Array.isArray(this.selected) && this.selected.length > 0;
                if (this.mode === 'range') return !!(this.selected && this.selected.start);
                return !!this.selected;
            },
        
            clear() {
                this.selected = this.mode === 'multiple' ? [] : (this.mode === 'range' ? { start: null, end: null } : null);
                this.hoverDate = null;
                this.focusedDate = null;
            },
        
            defaultFocusISO() {
                if (this.mode === 'single' && this.selected) return this.selected;
                if (this.mode === 'range' && this.selected?.start) return this.selected.start;
                if (this.mode === 'multiple' && Array.isArray(this.selected) && this.selected.length) {
                    return this.selected[this.selected.length - 1];
                }
                return this.toISO(this.today());
            },
        
            ensureFocusVisible() {
                const f = this.parseISO(this.focusedDate);
                const inView = this.panels.some(p => p.year === f.y && p.month === f.m);
                if (!inView) {
                    this.viewYear = f.y;
                    this.viewMonth = f.m;
                }
            },
        
            moveFocus(days) {
                if (!this.focusedDate) this.focusedDate = this.defaultFocusISO();
                const d = this.parseISO(this.focusedDate);
                const dt = new Date(d.y, d.m - 1, d.d);
                dt.setDate(dt.getDate() + days);
                this.focusedDate = `${dt.getFullYear()}-${this.pad(dt.getMonth() + 1)}-${this.pad(dt.getDate())}`;
                this.ensureFocusVisible();
            },
        
            moveFocusStartOfMonth() {
                if (!this.focusedDate) this.focusedDate = this.defaultFocusISO();
                const d = this.parseISO(this.focusedDate);
                this.focusedDate = `${d.y}-${this.pad(d.m)}-01`;
                this.ensureFocusVisible();
            },
        
            moveFocusEndOfMonth() {
                if (!this.focusedDate) this.focusedDate = this.defaultFocusISO();
                const d = this.parseISO(this.focusedDate);
                this.focusedDate = `${d.y}-${this.pad(d.m)}-${this.pad(this.daysInMonth(d.y, d.m))}`;
                this.ensureFocusVisible();
            },
        
            handleKeydown(e) {
                if (!this.open) {
                    if (['Enter', 'ArrowDown', ' '].includes(e.key)) {
                        e.preventDefault();
                        this.open = true;
                        this.focusedDate = this.defaultFocusISO();
                        this.ensureFocusVisible();
                    }
                    return;
                }
        
                switch (e.key) {
                    case 'ArrowRight':
                        e.preventDefault();
                        this.moveFocus(1);
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.moveFocus(-1);
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        this.moveFocus(7);
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        this.moveFocus(-7);
                        break;
                    case 'Home':
                        e.preventDefault();
                        this.moveFocusStartOfMonth();
                        break;
                    case 'End':
                        e.preventDefault();
                        this.moveFocusEndOfMonth();
                        break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        if (this.focusedDate) this.selectDate(this.parseISO(this.focusedDate));
                        break;
                    case 'Escape':
                        e.preventDefault();
                        this.open = false;
                        break;
                    case 'Tab':
                        this.open = false;
                        break;
                }
            },
        }"
        x-init="const t = today();
        let base = null;
        if (mode === 'single' && selected) base = parseISO(selected);
        else if (mode === 'range' && selected && selected.start) base = parseISO(selected.start);
        else if (mode === 'multiple' && Array.isArray(selected) && selected.length) base = parseISO(selected[selected.length - 1]);
        viewYear = base ? base.y : t.y;
        viewMonth = base ? base.m : t.m;"
        x-id="['flux-date-option']"
        @click.away="open = false; focusedDate = null"
        @keydown="handleKeydown($event)"
        tabindex="0"
        role="combobox"
        :aria-expanded="open"
        aria-haspopup="dialog"
        :class="typedInvalid ? 'border-red-500! ring-1! ring-red-500/20!' : ''"
        {{ $attributes->except(['wire:model'])->class($classes) }}
        data-flux-control
        @if ($invalid)
        aria-invalid="true" data-invalid
        @endif
        >
        {{-- Trigger --}}
        <div class="flex justify-between w-full items-center min-w-0 gap-2">
            <div
                class="flex items-center gap-2 min-w-0 flex-1"
                @if ($mode !== 'single') @click="open = !open" @endif
            >
                <flux:icon.calendar
                    class="size-4 text-zinc-400 shrink-0 cursor-pointer"
                    @click.stop="open = !open"
                />

                @if ($mode === 'single')
                    {{-- Editable: se puede escribir la fecha o seleccionarla en el calendario --}}
                    <input
                        type="text"
                        :value="typedText"
                        @input="handleTypedInput($event.target.value)"
                        @focus="open = true"
                        @keydown.stop
                        @keydown.enter.prevent="if (!typedInvalid && typedText.trim() !== '') open = false"
                        @keydown.escape="open = false; $event.target.blur()"
                        placeholder="{{ $placeholder }}"
                        autocomplete="off"
                        class="w-full min-w-0 bg-transparent border-none p-0 text-base sm:text-sm focus:outline-none focus:ring-0 truncate placeholder-zinc-400 dark:placeholder-zinc-500"
                        :class="typedInvalid ? 'text-red-500' : 'text-zinc-800 dark:text-zinc-100'"
                    >
                @else
                    <span
                        class="truncate"
                        :class="displayLabel ? 'text-zinc-800 dark:text-zinc-100' : 'text-zinc-400 dark:text-zinc-500'"
                        x-text="displayLabel ?? '{{ $placeholder }}'"
                    ></span>
                @endif
            </div>

            @if ($clearable)
                <span
                    x-show="hasValue"
                    x-cloak
                    @click.stop="clear()"
                    class="cursor-pointer text-zinc-400 hover:text-red-500 transition-colors shrink-0"
                >
                    <flux:icon.x-mark
                        variant="micro"
                        class="size-3.5"
                    />
                </span>
            @endif
        </div>

        {{-- Dropdown --}}
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1"
            @click.stop
            class="absolute left-0 top-full mt-1.5 z-[100] flex bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-white/10 rounded-xl shadow-xl p-3"
        >

            @if ($showPresets)
                <div
                    class="w-32 shrink-0 border-r border-zinc-100 dark:border-white/10 pr-3 mr-3 flex flex-col gap-0.5">
                    <template
                        x-for="preset in presetOptions"
                        :key="preset.key"
                    >
                        <button
                            type="button"
                            @click="applyPreset(preset.key)"
                            class="text-left text-sm px-2 py-1.5 rounded-md text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10 transition-colors"
                            x-text="preset.label"
                        ></button>
                    </template>
                </div>
            @endif

            <div
                class="flex gap-4"
                :class="panelsCount > 1 ? 'w-[520px]' : 'w-[260px]'"
            >
                <template
                    x-for="(panel, pIndex) in panels"
                    :key="pIndex"
                >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <button
                                type="button"
                                x-show="pIndex === 0"
                                :disabled="!canGoPrev"
                                @click="prevMonth()"
                                :class="canGoPrev ? 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-white/10 cursor-pointer' :
                                    'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'"
                                class="p-1 rounded-md transition-colors"
                            >
                                <flux:icon.chevron-left
                                    variant="micro"
                                    class="size-4"
                                />
                            </button>
                            <div
                                x-show="pIndex !== 0"
                                class="size-6"
                            ></div>

                            <div class="flex items-center gap-1 text-sm font-medium">
                                <select
                                    @click.stop
                                    x-model="panel.month"
                                    @change="setMonth($event.target.value)"
                                    class="bg-transparent border-none text-sm font-medium text-zinc-700 dark:text-zinc-200 focus:outline-none focus:ring-0 cursor-pointer"
                                >
                                    <template
                                        x-for="(name, idx) in monthNames"
                                        :key="idx"
                                    >
                                        <option
                                            :value="idx + 1"
                                            :selected="idx + 1 === panel.month"
                                            :disabled="isMonthOutOfBounds(panel.year, idx + 1)"
                                            x-text="name"
                                        ></option>
                                    </template>
                                </select>
                                <select
                                    @click.stop
                                    x-model="panel.year"
                                    @change="setYear($event.target.value)"
                                    class="bg-transparent border-none text-sm font-medium text-zinc-700 dark:text-zinc-200 focus:outline-none focus:ring-0 cursor-pointer"
                                >
                                    <template
                                        x-for="y in yearOptions"
                                        :key="y"
                                    >
                                        <option
                                            :value="y"
                                            :selected="y === 2026"
                                            x-text="y"
                                        ></option>
                                    </template>
                                </select>
                            </div>

                            <button
                                type="button"
                                x-show="pIndex === panelsCount - 1"
                                :disabled="!canGoNext"
                                @click="nextMonth()"
                                :class="canGoNext ? 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-white/10 cursor-pointer' :
                                    'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'"
                                class="p-1 rounded-md transition-colors"
                            >
                                <flux:icon.chevron-right
                                    variant="micro"
                                    class="size-4"
                                />
                            </button>
                            <div
                                x-show="pIndex !== panelsCount - 1"
                                class="size-6"
                            ></div>
                        </div>

                        <div class="grid grid-cols-7 gap-y-1 mb-1">
                            <template
                                x-for="dn in dayNames"
                                :key="dn"
                            >
                                <div
                                    class="text-center text-[11px] font-medium text-zinc-400 dark:text-zinc-500"
                                    x-text="dn"
                                ></div>
                            </template>
                        </div>

                        <div class="grid grid-cols-7 gap-y-1">
                            <template
                                x-for="(cell, cIndex) in panel.days"
                                :key="cIndex"
                            >
                                <div class="flex items-center justify-center h-8">
                                    <template x-if="cell === null">
                                        <span></span>
                                    </template>
                                    <template x-if="cell !== null">
                                        <button
                                            type="button"
                                            :id="cell ? $id('flux-date-option', toISO(cell)) : null"
                                            @click="selectDate(cell)"
                                            @mouseenter="mode === 'range' ? hoverDate = toISO(cell) : null"
                                            :disabled="isDisabled(cell)"
                                            :aria-selected="isSelected(cell)"
                                            :class="{
                                                'bg-zinc-800 text-white dark:bg-white dark:text-zinc-900 font-medium': isSelected(
                                                    cell),
                                                'bg-zinc-100 dark:bg-white/10': isInRange(cell) && !isSelected(cell),
                                                'ring-2 ring-zinc-400 dark:ring-white/40': isFocused(cell) && !
                                                    isSelected(cell),
                                                'ring-1 ring-inset ring-zinc-300 dark:ring-white/20': isToday(cell) && !
                                                    isSelected(cell) && !isFocused(cell),
                                                'text-zinc-300 dark:text-zinc-600 cursor-not-allowed': isDisabled(cell),
                                                'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10 cursor-pointer':
                                                    !isDisabled(cell) && !isSelected(cell),
                                            }"
                                            class="size-8 flex items-center justify-center text-sm rounded-lg transition-colors select-none"
                                            x-text="cell.d"
                                        >
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @if ($invalid && $name)
        <flux:error name="{{ $name }}" />
    @endif
</div>
