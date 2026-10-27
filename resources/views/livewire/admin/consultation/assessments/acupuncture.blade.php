@php
    use App\Support\AcupunctureCatalog;

    $acu = $assessmentForms['acupuntura'];
    $groups = [
        'lengua_forma' => 'Forma de la lengua',
        'lengua_color' => 'Color de la lengua',
        'lengua_otros_rasgos' => 'Otros rasgos',
        'saburra_color' => 'Capa lingual (saburra): color',
        'saburra_espesor' => 'Capa lingual: espesor',
        'saburra_distribucion' => 'Capa lingual: distribución',
    ];
    $multi = AcupunctureCatalog::multiOptions();
    $relations = AcupunctureCatalog::relaciones($acu['organos_zang_fu'] ?? []);
@endphp

<flux:card class="space-y-6">
    <flux:heading size="lg">Evaluación de acupuntura</flux:heading>
    <flux:textarea label="Evaluación energética" wire:model="assessmentForms.acupuntura.diagnostico_energetico" rows="3" />

    <div class="space-y-3">
        <flux:heading size="sm">B.1 Pulsos</flux:heading>
        <flux:text class="text-sm">Registra la calidad de cada posición (normal, vacío, lleno, rápido, lento, tenso, resbaladizo…).</flux:text>
        <datalist id="pulse-qualities">
            @foreach (AcupunctureCatalog::CALIDADES_PULSO as $quality)
                <option value="{{ $quality }}"></option>
            @endforeach
        </datalist>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (AcupunctureCatalog::pulsos() as $key => [$label, $organ])
                <flux:input
                    label="{{ $label }}"
                    description="{{ $organ }}"
                    list="pulse-qualities"
                    wire:model="assessmentForms.acupuntura.pulsos.{{ $key }}.calidad"
                />
                @error("assessmentForms.acupuntura.pulsos.{$key}.calidad")
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            @endforeach
        </div>
    </div>

    <div class="space-y-4">
        <flux:heading size="sm">B.2 Diagnóstico de lengua</flux:heading>
        <div class="grid gap-5 md:grid-cols-2">
            @foreach ($groups as $field => $label)
                <flux:checkbox.group wire:model="assessmentForms.acupuntura.{{ $field }}" label="{{ $label }}">
                    @foreach ($multi[$field] as $value => $text)
                        <flux:checkbox value="{{ $value }}" label="{{ $text }}" />
                    @endforeach
                </flux:checkbox.group>
            @endforeach
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <flux:select label="Humedad" wire:model="assessmentForms.acupuntura.humedad">
                <flux:select.option value="">Sin especificar</flux:select.option>
                @foreach (AcupunctureCatalog::HUMEDADES as $value => $text)
                    <flux:select.option value="{{ $value }}">{{ $text }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select label="Movimiento de la lengua" wire:model="assessmentForms.acupuntura.lengua_movimiento">
                <flux:select.option value="">Sin especificar</flux:select.option>
                @foreach (AcupunctureCatalog::MOVIMIENTOS_LENGUA as $value => $text)
                    <flux:select.option value="{{ $value }}">{{ $text }}</flux:select.option>
                @endforeach
            </flux:select>
            @foreach ($assessmentComplementarySections['acupuntura']['fields'] as $field => $label)
                <flux:input
                    label="{{ $label }}"
                    wire:model="assessmentForms.acupuntura.datos_complementarios.{{ $field }}"
                />
            @endforeach
        </div>
    </div>

    <div class="space-y-3">
        <flux:heading size="sm">B.3 Diagnóstico de las 8 reglas</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (AcupunctureCatalog::ochoReglas() as $axis => [$label, $poles])
                <flux:radio.group wire:model="assessmentForms.acupuntura.diagnostico_8_reglas.{{ $axis }}" label="{{ $label }}">
                    @foreach ($poles as $value => $text)
                        <flux:radio value="{{ $value }}" label="{{ $text }}" />
                    @endforeach
                </flux:radio.group>
            @endforeach
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <flux:checkbox.group wire:model="assessmentForms.acupuntura.diagnostico_6_niveles" label="B.4 Diagnóstico de los 6 niveles">
            @foreach ($multi['diagnostico_6_niveles'] as $value => $text)
                <flux:checkbox value="{{ $value }}" label="{{ $text }}" />
            @endforeach
        </flux:checkbox.group>
        <flux:checkbox.group wire:model="assessmentForms.acupuntura.diagnostico_4_capas" label="B.5 Diagnóstico de las 4 capas">
            @foreach ($multi['diagnostico_4_capas'] as $value => $text)
                <flux:checkbox value="{{ $value }}" label="{{ $text }}" />
            @endforeach
        </flux:checkbox.group>
    </div>

    <div class="space-y-3">
        <flux:checkbox.group wire:model.live="assessmentForms.acupuntura.organos_zang_fu" label="B.6 Órganos afectados Zang-Fu">
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($multi['organos_zang_fu'] as $value => $text)
                    <flux:checkbox value="{{ $value }}" label="{{ $text }}" />
                @endforeach
            </div>
        </flux:checkbox.group>
        @if ($relations !== [])
            <div class="overflow-x-auto" data-testid="zangfu-relations">
                <table class="w-full text-left text-sm">
                    <thead class="text-zinc-500">
                        <tr>
                            <th class="py-1 pr-4">Generación o nutrición</th>
                            <th class="py-1 pr-4">Órgano afectado</th>
                            <th class="py-1">Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($relations as $row)
                            <tr>
                                <td class="py-1 pr-4">{{ $row['generacion'] }}</td>
                                <td class="py-1 pr-4">{{ $row['organo'] }} ({{ $row['elemento'] }})</td>
                                <td class="py-1">{{ $row['control'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="space-y-3">
        <flux:heading size="sm">B.7 Protocolo</flux:heading>
        @foreach (AcupunctureCatalog::protocolo() as $block => $label)
            <div class="grid gap-3 rounded-lg border border-zinc-200 p-3 md:grid-cols-3 dark:border-zinc-700">
                <flux:input
                    label="{{ $label }}"
                    placeholder="Ej.: IG4, H3"
                    wire:model="assessmentForms.acupuntura.puntos_sugeridos.{{ $block }}.puntos"
                />
                <flux:input
                    label="Técnica o método"
                    wire:model="assessmentForms.acupuntura.puntos_sugeridos.{{ $block }}.tecnica"
                />
                <flux:input
                    label="Observaciones"
                    wire:model="assessmentForms.acupuntura.puntos_sugeridos.{{ $block }}.observaciones"
                />
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <flux:textarea label="Principio terapéutico" wire:model="assessmentForms.acupuntura.principio_terapeutico" rows="3" />
        <flux:textarea label="Observaciones de evaluación energética" wire:model="assessmentForms.acupuntura.observaciones" rows="3" />
    </div>
</flux:card>
