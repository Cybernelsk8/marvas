<flux:card class="space-y-5">
    <flux:heading size="lg">Evaluación homeopática</flux:heading>
    <flux:textarea label="Aspecto del paciente" wire:model="assessmentForms.homeopatia.aspecto_paciente" rows="3" />
    <flux:textarea label="Síntomas locales y generales" wire:model="assessmentForms.homeopatia.sintomas_locales_generales" rows="4" />
    <flux:textarea label="Síntomas mentales" wire:model="assessmentForms.homeopatia.sintomas_mentales" rows="4" />
    <flux:textarea label="Antecedentes biopatográficos" wire:model="assessmentForms.homeopatia.sintomas_biopatograficos" rows="4" />
    <div class="grid gap-5 md:grid-cols-2">
        <flux:textarea label="Diagnóstico posible" wire:model="assessmentForms.homeopatia.diagnostico_posible" rows="3" />
        <flux:textarea label="Tratamiento indicado" wire:model="assessmentForms.homeopatia.tratamiento_indicado" rows="3" />
    </div>
    <flux:textarea label="Observaciones de evaluación homeopática" wire:model="assessmentForms.homeopatia.observaciones" rows="3" />
</flux:card>
