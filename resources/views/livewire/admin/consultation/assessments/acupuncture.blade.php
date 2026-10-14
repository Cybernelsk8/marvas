@php
    $pulseFields = [
        'pulso_cun_der_superficial' => 'Cun derecho superficial',
        'pulso_cun_der_profundo' => 'Cun derecho profundo',
        'pulso_guan_der_superficial' => 'Guan derecho superficial',
        'pulso_guan_der_profundo' => 'Guan derecho profundo',
        'pulso_chi_der_superficial' => 'Chi derecho superficial',
        'pulso_chi_der_profundo' => 'Chi derecho profundo',
        'pulso_cun_izq_superficial' => 'Cun izquierdo superficial',
        'pulso_cun_izq_profundo' => 'Cun izquierdo profundo',
        'pulso_guan_izq_superficial' => 'Guan izquierdo superficial',
        'pulso_guan_izq_profundo' => 'Guan izquierdo profundo',
        'pulso_chi_izq_superficial' => 'Chi izquierdo superficial',
        'pulso_chi_izq_profundo' => 'Chi izquierdo profundo',
    ];
    $arrayFields = [
        'lengua_forma' => 'Forma de la lengua',
        'lengua_color' => 'Color de la lengua',
        'lengua_otros_rasgos' => 'Otros rasgos de la lengua',
        'saburra_color' => 'Color de la saburra',
        'saburra_espesor' => 'Espesor de la saburra',
        'saburra_distribucion' => 'Distribución de la saburra',
        'diagnostico_8_reglas' => 'Diagnóstico de ocho reglas',
        'diagnostico_6_niveles' => 'Diagnóstico de seis niveles',
        'diagnostico_4_capas' => 'Diagnóstico de cuatro capas',
        'organos_zang_fu' => 'Órganos Zang Fu',
        'puntos_sugeridos' => 'Puntos sugeridos (uno por línea)',
    ];
@endphp

<flux:card class="space-y-5">
    <flux:heading size="lg">Evaluación de acupuntura</flux:heading>
    <flux:textarea label="Evaluación energética" wire:model="assessmentForms.acupuntura.diagnostico_energetico" rows="3" />
    <div>
        <flux:heading size="sm">Pulsos</flux:heading>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($pulseFields as $field => $label)
                <flux:input label="{{ $label }}" wire:model="assessmentForms.acupuntura.{{ $field }}" />
            @endforeach
        </div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($arrayFields as $field => $label)
            <flux:textarea
                label="{{ $label }} (uno por línea)"
                wire:model="assessmentForms.acupuntura.{{ $field }}"
                rows="3"
            />
        @endforeach
    </div>
    <flux:select label="Humedad" wire:model="assessmentForms.acupuntura.humedad">
        <flux:select.option value="">Sin especificar</flux:select.option>
        <flux:select.option value="seca">Seca</flux:select.option>
        <flux:select.option value="humeda">Húmeda</flux:select.option>
        <flux:select.option value="pegajosa">Pegajosa</flux:select.option>
    </flux:select>
    <div class="grid gap-5 md:grid-cols-2">
        <flux:textarea label="Principio terapéutico" wire:model="assessmentForms.acupuntura.principio_terapeutico" rows="3" />
        <flux:textarea label="Observaciones de evaluación energética" wire:model="assessmentForms.acupuntura.observaciones" rows="3" />
    </div>
</flux:card>
