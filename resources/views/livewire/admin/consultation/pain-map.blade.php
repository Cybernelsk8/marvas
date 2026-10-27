@php
    use App\Support\PainMapCatalog;

    $niveles = PainMapCatalog::niveles();
    $nombres = $this->regions->pluck('nombre', 'codigo');
    $selectedRegion = $selected ? $this->regions->firstWhere('id', (int) explode('-', $selected)[0]) : null;
    $selectedLado = $selected ? explode('-', $selected)[1] : null;
    $history = $this->history;
@endphp

<div class="space-y-6">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,24rem)]">
        <div
            class="space-y-3"
            x-data="{
                hover: null,
                boxes: { ambas: '95 45 950 1260', anterior: '95 45 580 1260', posterior: '585 45 460 1260' },
            }"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:radio.group wire:model.live="view" variant="segmented" size="sm">
                    <flux:radio value="ambas" label="Ambas" />
                    <flux:radio value="anterior" label="Anterior" />
                    <flux:radio value="posterior" label="Posterior" />
                </flux:radio.group>
                <div class="min-h-6 text-sm font-medium" x-text="hover ?? 'Pasa el mouse sobre una región'"></div>
            </div>

            <svg
                x-bind:viewBox="boxes[$wire.view]"
                class="mx-auto h-[36rem] w-full rounded border border-zinc-200 bg-white dark:border-zinc-700"
                role="group"
                aria-label="Mapa corporal de dolor"
            >
                <image href="{{ asset('img/mapa-corporal.png') }}" x="0" y="0" width="1122" height="1402" />
                @foreach ($this->regions as $region)
                    @foreach ($region->trazos as $lado => $d)
                        @php
                            $key = PainMapCatalog::key($region->id, $lado);
                            $zoneEva = isset($zones[$key]) ? (int) $zones[$key]['eva'] : null;
                            $previous = $this->compareZones[$key] ?? null;
                            $isSelected = $selected === $key;
                            $label = $region->nombre.($lado === 'central' ? '' : ' ('.$lado.')');
                        @endphp
                        <path
                            wire:key="zone-{{ $key }}"
                            d="{{ $d }}"
                            fill="{{ $zoneEva !== null ? PainMapCatalog::color($zoneEva) : '#71717a' }}"
                            fill-opacity="{{ $zoneEva !== null ? 0.6 : 0.08 }}"
                            stroke="{{ $isSelected ? '#2563eb' : ($previous !== null ? PainMapCatalog::color($previous) : ($zoneEva !== null ? '#7f1d1d' : '#a1a1aa')) }}"
                            stroke-width="{{ $isSelected ? 4 : ($previous !== null ? 3 : 1.2) }}"
                            @if ($previous !== null) stroke-dasharray="8 5" @endif
                            class="cursor-pointer transition-opacity hover:opacity-100"
                            style="outline: none"
                            tabindex="0"
                            role="button"
                            aria-label="{{ $label }}{{ $zoneEva !== null ? ', EVA '.$zoneEva : '' }}"
                            x-on:mouseenter="hover = @js($label.($zoneEva !== null ? ' · EVA '.$zoneEva : ''))"
                            x-on:mouseleave="hover = null"
                            x-on:click="$wire.select({{ $region->id }}, '{{ $lado }}')"
                            x-on:keydown.enter="$wire.select({{ $region->id }}, '{{ $lado }}')"
                        >
                            <title>{{ $label }}</title>
                        </path>
                    @endforeach
                @endforeach
            </svg>

            <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-600 dark:text-zinc-300">
                @foreach ([0 => 'Sin dolor', 2 => 'Leve', 5 => 'Moderado', 8 => 'Intenso', 10 => 'Muy intenso'] as $eva => $name)
                    <span class="inline-flex items-center gap-1">
                        <span class="inline-block size-3 rounded-sm" style="background: {{ PainMapCatalog::color($eva) }}"></span>{{ $name }}
                    </span>
                @endforeach
                @if ($this->compareZones)
                    <span class="inline-flex items-center gap-1">
                        <span class="inline-block h-0 w-5 border-t-2 border-dashed border-zinc-500"></span>Contorno punteado: sesión comparada
                    </span>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            @if ($selectedRegion)
                <flux:card class="space-y-4" wire:key="zone-panel-{{ $selected }}">
                    <flux:heading size="lg">
                        {{ $selectedRegion->nombre }}@if ($selectedLado !== 'central') <span class="text-zinc-500">· {{ ucfirst($selectedLado) }}</span>@endif
                    </flux:heading>

                    @if ($editable)
                        <div>
                            <div class="flex items-center justify-between">
                                <flux:label>Intensidad EVA (0-10)</flux:label>
                                <flux:badge :color="$zone['eva'] >= 7 ? 'red' : ($zone['eva'] >= 4 ? 'amber' : 'green')">
                                    {{ $zone['eva'] }} · {{ $niveles[\App\Models\ZonaDolor::nivelDesdeEva((int) $zone['eva'])] }}
                                </flux:badge>
                            </div>
                            <input type="range" min="0" max="10" step="1" wire:model.live="zone.eva" class="mt-2 w-full" />
                            <flux:error name="zone.eva" />
                        </div>

                        <flux:checkbox.group wire:model="zone.tipos" label="Tipo de dolor">
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                @foreach (PainMapCatalog::tipos() as $value => $label)
                                    <flux:checkbox :value="$value" :label="$label" />
                                @endforeach
                            </div>
                        </flux:checkbox.group>

                        <flux:checkbox.group wire:model="zone.patron" label="Patrón / horario">
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                @foreach (PainMapCatalog::patrones() as $value => $label)
                                    <flux:checkbox :value="$value" :label="$label" />
                                @endforeach
                            </div>
                        </flux:checkbox.group>

                        <flux:input label="Inicio" placeholder="Ej.: hace 3 semanas" wire:model="zone.inicio" />
                        <flux:error name="zone.inicio" />

                        <details class="rounded border border-zinc-200 p-2 dark:border-zinc-700">
                            <summary class="cursor-pointer text-sm font-medium">Irradiación ({{ count($zone['irradiacion']) }})</summary>
                            <div class="mt-2 max-h-56 overflow-y-auto">
                                <flux:checkbox.group wire:model="zone.irradiacion">
                                    @foreach ($nombres as $codigo => $nombre)
                                        <flux:checkbox :value="$codigo" :label="$nombre" />
                                    @endforeach
                                </flux:checkbox.group>
                            </div>
                        </details>

                        <flux:textarea label="Comentario" rows="2" wire:model="zone.comentario" />
                        <flux:error name="zone.comentario" />

                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="cancelZone">Cancelar</flux:button>
                            <flux:button size="sm" variant="primary" wire:click="applyZone">Aplicar zona</flux:button>
                        </div>
                    @endif

                    <div class="space-y-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        <flux:heading size="sm">Evolución de esta zona</flux:heading>
                        @if (count($history) >= 1)
                            @php
                                $n = count($history);
                                $pts = collect($history)->map(fn ($p, $i) => [
                                    $n > 1 ? 10 + ($i * 180 / ($n - 1)) : 100,
                                    55 - $p['eva'] * 5,
                                ]);
                            @endphp
                            <svg viewBox="0 0 200 70" class="w-full" role="img" aria-label="Evolución del EVA">
                                <line x1="10" y1="5" x2="190" y2="5" stroke="#e4e4e7" />
                                <line x1="10" y1="55" x2="190" y2="55" stroke="#a1a1aa" />
                                <polyline fill="none" stroke="#2563eb" stroke-width="1.5" points="{{ $pts->map(fn ($p) => $p[0].','.$p[1])->implode(' ') }}" />
                                @foreach ($pts as $i => $p)
                                    <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="3" fill="{{ PainMapCatalog::color($history[$i]['eva']) }}" stroke="#1e3a8a" />
                                @endforeach
                            </svg>
                            <ul class="space-y-1 text-sm">
                                @foreach ($history as $point)
                                    <li class="flex justify-between"><span>{{ $point['label'] }}</span><span class="font-medium">EVA {{ $point['eva'] }}</span></li>
                                @endforeach
                            </ul>
                        @else
                            <flux:text class="text-sm">Sin registros previos de esta zona.</flux:text>
                        @endif
                    </div>
                </flux:card>
            @else
                <flux:callout icon="cursor-arrow-rays" color="zinc">
                    <flux:callout.text>
                        {{ $editable ? 'Haz clic en una región del mapa para registrar su dolor, o elígela desde la lista.' : 'Haz clic en una región para ver su historial.' }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            @if ($editable)
                <flux:dropdown>
                    <flux:button size="sm" icon="plus" icon:trailing="chevron-down">Agregar desde lista</flux:button>
                    <flux:menu class="max-h-80 overflow-y-auto">
                        @foreach ($this->regions as $region)
                            @foreach (array_keys($region->trazos) as $lado)
                                <flux:menu.item wire:click="select({{ $region->id }}, '{{ $lado }}')" wire:key="list-{{ $region->id }}-{{ $lado }}">
                                    {{ $region->nombre }}{{ $lado === 'central' ? '' : ' · '.ucfirst($lado) }}
                                    <span class="text-xs text-zinc-500">({{ $region->vista }})</span>
                                </flux:menu.item>
                            @endforeach
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>
    </div>

    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">Zonas registradas</flux:heading>
            @if ($this->previousConsultations->isNotEmpty())
                <flux:select wire:model.live="compareId" placeholder="Comparar con sesión anterior…" class="max-w-xs">
                    <flux:select.option value="">Sin comparación</flux:select.option>
                    @foreach ($this->previousConsultations as $c)
                        <flux:select.option :value="$c->id">
                            Consulta #{{ $c->numero_consulta }} · {{ $c->fecha_hora?->format('d/m/Y') }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif
        </div>

        @if ($summary->isEmpty())
            <flux:text>No hay zonas con dolor registradas.</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Región</flux:table.column>
                    <flux:table.column>Lado</flux:table.column>
                    <flux:table.column>EVA</flux:table.column>
                    <flux:table.column>Nivel</flux:table.column>
                    @if ($this->compareZones)
                        <flux:table.column>Anterior</flux:table.column>
                        <flux:table.column>Cambio</flux:table.column>
                    @endif
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($summary as $row)
                        <flux:table.row wire:key="summary-{{ $row['key'] }}">
                            <flux:table.cell>{{ $row['region'] }}</flux:table.cell>
                            <flux:table.cell>{{ $row['lateralidad'] === 'central' ? '—' : ucfirst($row['lateralidad']) }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="inline-block size-3 rounded-sm align-middle" style="background: {{ PainMapCatalog::color($row['eva']) }}"></span>
                                {{ $row['eva'] }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $niveles[\App\Models\ZonaDolor::nivelDesdeEva($row['eva'])] }}</flux:table.cell>
                            @if ($this->compareZones)
                                <flux:table.cell>{{ $row['previous'] ?? '—' }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($row['previous'] === null)
                                        <flux:badge color="blue" size="sm">Nueva</flux:badge>
                                    @else
                                        @php $delta = $row['eva'] - $row['previous']; @endphp
                                        <flux:badge :color="$delta < 0 ? 'green' : ($delta > 0 ? 'red' : 'zinc')" size="sm">
                                            {{ $delta > 0 ? '+' : '' }}{{ $delta }}
                                        </flux:badge>
                                    @endif
                                </flux:table.cell>
                            @endif
                            <flux:table.cell class="text-right">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="select({{ $row['region_id'] }}, '{{ $row['lateralidad'] }}')" aria-label="Editar zona" />
                                @if ($editable)
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="removeZone('{{ $row['key'] }}')" aria-label="Quitar zona" />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    @if ($editable)
        <div class="space-y-4">
            <flux:checkbox label="El paciente no refiere dolor" wire:model.live="noPain" :disabled="$summary->isNotEmpty()" />
            <flux:textarea label="Observaciones del mapa" rows="2" wire:model="observaciones" />
            <flux:error name="observaciones" />
            @error('mapa')
                <flux:callout variant="danger" icon="exclamation-triangle"><flux:callout.text>{{ $message }}</flux:callout.text></flux:callout>
            @enderror
            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">Guardar mapa de dolor</flux:button>
            </div>
        </div>
    @endif
</div>
