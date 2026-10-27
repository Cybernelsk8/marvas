@php
    use App\Support\AnamnesisCatalog;

    $activeAlerts = collect($assessmentForms['quiropraxia']['seguridad'] ?? [])
        ->filter(fn (array $item): bool => $item['estado'] === 'si')
        ->keys()
        ->map(fn (string $key): string => AnamnesisCatalog::seguridad()[$key][0]);
@endphp

<flux:card class="space-y-5">
    <flux:heading size="lg">Evaluación quiropráctica / neuromusculoesquelética</flux:heading>

    <flux:heading size="sm">06 Síntomas actuales</flux:heading>
    @include('livewire.admin.consultation.partials.state-block', [
        'model' => 'assessmentForms.quiropraxia.sintomas',
        'catalog' => AnamnesisCatalog::sintomas(),
        'estados' => AnamnesisCatalog::ESTADOS['sintomas'],
    ])

    <flux:heading size="sm">06A Postura y movilidad</flux:heading>
    @include('livewire.admin.consultation.partials.state-block', [
        'model' => 'assessmentForms.quiropraxia.postura_movilidad',
        'catalog' => AnamnesisCatalog::posturaMovilidad(),
        'estados' => AnamnesisCatalog::ESTADOS['exploracion_a'],
    ])

    <flux:heading size="sm">06B Pruebas neurológicas y ortopédicas</flux:heading>
    @include('livewire.admin.consultation.partials.state-block', [
        'model' => 'assessmentForms.quiropraxia.pruebas_neuro_ortopedicas',
        'catalog' => AnamnesisCatalog::pruebas(),
        'estados' => AnamnesisCatalog::ESTADOS['exploracion_b'],
        'withTest' => true,
    ])
    <flux:textarea label="Otras pruebas" wire:model="assessmentForms.quiropraxia.otras_pruebas" rows="3" />

    <flux:heading size="sm">06C Índice funcional</flux:heading>
    @include('livewire.admin.consultation.partials.state-block', [
        'model' => 'assessmentForms.quiropraxia.indice_funcional',
        'catalog' => AnamnesisCatalog::indiceFuncional(),
        'estados' => AnamnesisCatalog::ESTADOS['indice'],
        'blank' => true,
    ])

    <flux:heading size="sm">06D Señales de alarma y seguridad</flux:heading>
    @include('livewire.admin.consultation.partials.state-block', [
        'model' => 'assessmentForms.quiropraxia.seguridad',
        'catalog' => AnamnesisCatalog::seguridad(),
        'estados' => AnamnesisCatalog::ESTADOS['tri'],
    ])
    @if ($activeAlerts->isNotEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle" data-testid="safety-alert">
            <flux:callout.heading>Alertas de seguridad activas</flux:callout.heading>
            <flux:callout.text>
                {{ $activeAlerts->implode('; ') }}. Documenta la valoración y referencia antes de manipular;
                la manipulación no puede marcarse como indicada.
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <flux:select label="Decisión sobre la manipulación" wire:model="assessmentForms.quiropraxia.decision_manipulacion">
            <flux:select.option value="">Sin especificar</flux:select.option>
            <flux:select.option value="indicada">Indicada</flux:select.option>
            <flux:select.option value="diferida">Diferida</flux:select.option>
            <flux:select.option value="contraindicada">Contraindicada</flux:select.option>
        </flux:select>
        <flux:textarea label="Motivo de la decisión" wire:model="assessmentForms.quiropraxia.motivo_decision" rows="3" />
    </div>
    @error('assessmentForms.quiropraxia.decision_manipulacion')
        <flux:error>{{ $message }}</flux:error>
    @enderror

    <div class="grid gap-5 md:grid-cols-2">
        <flux:textarea label="Diagnóstico" wire:model="assessmentForms.quiropraxia.diagnostico" rows="3" />
        <flux:textarea label="Plan inicial de seguimiento" wire:model="assessmentForms.quiropraxia.plan_seguimiento_inicial" rows="3" />
    </div>
    <flux:textarea label="Observaciones de evaluación quiropráctica" wire:model="assessmentForms.quiropraxia.observaciones" rows="3" />
</flux:card>
