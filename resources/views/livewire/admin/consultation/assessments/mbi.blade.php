<flux:card class="space-y-5">
    <flux:text>
        Registra la integración del evaluador. Los sistemas y selecciones del Modelo 6+ se conservan como listas para no imponer
        un catálogo clínico aún pendiente de definición.
    </flux:text>
    <flux:textarea
        label="Integración clínica"
        wire:model="mbiForm.integracion_clinica"
        rows="4"
    />
    <flux:textarea
        label="Sistemas comprometidos (uno por línea)"
        wire:model="mbiForm.sistemas_comprometidos"
        rows="4"
    />
    <flux:textarea
        label="Hallazgos principales"
        wire:model="mbiForm.hallazgos_principales"
        rows="4"
    />
    <flux:textarea
        label="Objetivo terapéutico"
        wire:model="mbiForm.objetivo_terapeutico"
        rows="3"
    />
    <div class="grid gap-5 md:grid-cols-2">
        <flux:input
            label="Especialidad principal"
            wire:model="mbiForm.especialidad_principal"
        />
        <flux:textarea
            label="Especialidades complementarias (una por línea)"
            wire:model="mbiForm.especialidades_complementarias"
            rows="3"
        />
    </div>
    <flux:textarea
        label="Selección del Modelo 6+ (una por línea)"
        wire:model="mbiForm.modelo_6plus_seleccion"
        rows="4"
    />
    <flux:textarea
        label="Observaciones generales"
        wire:model="mbiForm.observaciones_generales"
        rows="4"
    />
</flux:card>
