<section class="mx-auto w-full max-w-7xl space-y-6 p-0 lg:p-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Regiones corporales</flux:heading>
            <flux:text class="mt-2">Catálogo de regiones del mapa de dolor. Dibuja el contorno de cada región sobre la imagen para que sea seleccionable.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="resetForm" x-on:click="$flux.modal('body-region-form').show()">
            Nueva región
        </flux:button>
    </header>

    <x-data-table :headers="$this->headers" :rows="$this->rows">
        @interact('vista', $row)
            {{ $row->vista === 'anterior' ? 'Anterior' : 'Posterior' }}
        @endinteract

        @interact('trazado', $row)
            <flux:badge :color="$row->trazos ? 'green' : 'amber'">
                {{ $row->trazos ? 'Trazado' : 'Sin trazar' }}
            </flux:badge>
        @endinteract

        @interact('activo', $row)
            <flux:badge :color="$row->activo ? 'green' : 'zinc'">
                {{ $row->activo ? 'Activa' : 'Inactiva' }}
            </flux:badge>
        @endinteract

        @interact('actions', $row)
            <flux:dropdown>
                <flux:button size="sm" icon="ellipsis-vertical" class="cursor-pointer" variant="ghost" />
                <flux:menu>
                    <flux:menu.item icon="pencil-square" wire:click="edit({{ $row->id }})">Editar y trazar</flux:menu.item>
                    <flux:menu.item
                        :icon="$row->activo ? 'x-circle' : 'check-circle'"
                        :variant="$row->activo ? 'danger' : 'default'"
                        wire:click="toggleActive({{ $row->id }})"
                    >
                        {{ $row->activo ? 'Desactivar' : 'Activar' }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endinteract
    </x-data-table>

    <flux:modal name="body-region-form" class="w-full max-w-5xl">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? 'Editar región' : 'Nueva región' }}</flux:heading>

            <div class="grid gap-6 lg:grid-cols-[1fr_minmax(0,22rem)]">
                <div class="space-y-4">
                    <flux:input label="Nombre *" wire:model.live.debounce.300ms="nombre" />
                    <flux:error name="nombre" />
                    <flux:input label="Código *" wire:model="codigo" />
                    <flux:error name="codigo" />
                    <flux:select label="Vista *" wire:model.live="vista">
                        <flux:select.option value="anterior">Anterior</flux:select.option>
                        <flux:select.option value="posterior">Posterior</flux:select.option>
                    </flux:select>
                    <flux:input type="number" min="0" label="Orden" wire:model="orden" />
                    <flux:checkbox label="Tiene lateralidad (derecha / izquierda)" wire:model.live="tiene_lateralidad" />
                    <flux:checkbox label="Región activa" wire:model="activo" />
                    <flux:error name="trazos.*" />
                </div>

                <div
                    class="space-y-3"
                    x-data="{
                        lado: 'central',
                        lados() { return $wire.tiene_lateralidad ? ['derecha', 'izquierda'] : ['central'] },
                        pts() {
                            const p = ($wire.trazos || {})[this.lado];
                            const n = p ? (p.match(/-?\d+(\.\d+)?/g) || []) : [];
                            const out = [];
                            for (let i = 0; i + 1 < n.length; i += 2) out.push([+n[i], +n[i + 1]]);
                            return out;
                        },
                        path(pts) { return pts.map((p, i) => (i ? 'L' : 'M') + p[0] + ' ' + p[1]).join(' ') + ' Z' },
                        set(pts) {
                            const t = { ...($wire.trazos || {}) };
                            if (pts.length) { t[this.lado] = this.path(pts) } else { delete t[this.lado] }
                            $wire.$set('trazos', t, false);
                        },
                        add(e) {
                            const svg = this.$refs.svg;
                            const pt = svg.createSVGPoint();
                            pt.x = e.clientX; pt.y = e.clientY;
                            const c = pt.matrixTransform(svg.getScreenCTM().inverse());
                            this.set([...this.pts(), [Math.round(c.x), Math.round(c.y)]]);
                        },
                        undo() { this.set(this.pts().slice(0, -1)) },
                        clear() { this.set([]) },
                        box() { return $wire.vista === 'posterior' ? '585 45 460 1260' : '95 45 580 1260' },
                    }"
                    x-effect="if (!lados().includes(lado)) lado = lados()[0]"
                >
                    <flux:text class="text-sm">Elige el lado y haz clic sobre la imagen para marcar los vértices del contorno. Deben ser 3 o más.</flux:text>

                    <div class="flex flex-wrap items-center gap-2">
                        <template x-for="l in lados()" :key="l">
                            <flux:button type="button" size="sm" x-on:click="lado = l" x-bind:variant="lado === l ? 'primary' : 'ghost'">
                                <span x-text="l.charAt(0).toUpperCase() + l.slice(1)"></span>
                            </flux:button>
                        </template>
                        <flux:button type="button" size="sm" variant="ghost" icon="arrow-uturn-left" x-on:click="undo()">Deshacer</flux:button>
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" x-on:click="clear()">Limpiar lado</flux:button>
                    </div>

                    <svg x-ref="svg" x-bind:viewBox="box()" class="h-[34rem] w-full cursor-crosshair rounded border border-zinc-200 bg-white dark:border-zinc-700" x-on:click="add($event)">
                        <image href="{{ asset('img/mapa-corporal.png') }}" x="0" y="0" width="1122" height="1402" />
                        @foreach ($this->referencias as $ref)
                            @foreach ($ref['paths'] as $d)
                                <path d="{{ $d }}" fill="rgba(120,120,120,.18)" stroke="rgba(120,120,120,.6)" stroke-width="1.5" pointer-events="none" />
                            @endforeach
                        @endforeach
                        <template x-for="l in lados()" :key="'p' + l">
                            <path x-bind:d="($wire.trazos || {})[l] || ''" x-bind:fill="l === lado ? 'rgba(37,99,235,.3)' : 'rgba(37,99,235,.12)'" stroke="#2563eb" stroke-width="2" pointer-events="none" />
                        </template>
                        <template x-for="(p, i) in pts()" :key="i">
                            <circle x-bind:cx="p[0]" x-bind:cy="p[1]" r="5" fill="#dc2626" pointer-events="none" />
                        </template>
                    </svg>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button type="button" variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
