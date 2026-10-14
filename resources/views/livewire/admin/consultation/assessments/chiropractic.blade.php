<flux:card class="space-y-5">
    <flux:heading size="lg">Evaluación quiropráctica</flux:heading>
    <div class="grid gap-5 md:grid-cols-2">
        <flux:textarea label="Cirugías" wire:model="assessmentForms.quiropraxia.cirugias" rows="3" />
        <flux:textarea label="Fracturas" wire:model="assessmentForms.quiropraxia.fracturas" rows="3" />
        <flux:textarea label="Hospitalizaciones" wire:model="assessmentForms.quiropraxia.hospitalizaciones" rows="3" />
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:checkbox label="Implantes metálicos" wire:model="assessmentForms.quiropraxia.implantes_metales" />
        <flux:checkbox label="Embarazo" wire:model="assessmentForms.quiropraxia.embarazo" />
        <flux:checkbox label="Adormecimientos" wire:model="assessmentForms.quiropraxia.adormecimientos" />
        <flux:checkbox label="Ciática" wire:model="assessmentForms.quiropraxia.ciatica" />
        <flux:checkbox label="Dolor de cabeza" wire:model="assessmentForms.quiropraxia.dolor_cabeza" />
        <flux:checkbox label="Vértigo" wire:model="assessmentForms.quiropraxia.vertigo" />
        <flux:checkbox label="ATM" wire:model="assessmentForms.quiropraxia.atm" />
    </div>
    <div class="grid gap-5 md:grid-cols-2">
        <flux:textarea label="Detalle de implantes" wire:model="assessmentForms.quiropraxia.detalle_implantes" rows="3" />
        <flux:input
            label="Semanas de gestación"
            type="number"
            min="1"
            max="42"
            wire:model="assessmentForms.quiropraxia.semanas_gestacion"
        />
        <flux:textarea label="Zonas adormecidas" wire:model="assessmentForms.quiropraxia.zonas_adormecidas" rows="3" />
        <flux:textarea label="Detalle de ATM" wire:model="assessmentForms.quiropraxia.detalle_atm" rows="3" />
        <flux:textarea label="Postura" wire:model="assessmentForms.quiropraxia.postura" rows="3" />
    </div>
    <flux:heading size="sm">Evaluación funcional</flux:heading>
    <div class="grid gap-4 sm:grid-cols-2">
        <flux:select label="Calidad del sueño" wire:model="assessmentForms.quiropraxia.calidad_sueno">
            <flux:select.option value="">Sin especificar</flux:select.option>
            <flux:select.option value="bueno">Bueno</flux:select.option>
            <flux:select.option value="ligero">Ligero</flux:select.option>
            <flux:select.option value="moderado">Moderado</flux:select.option>
            <flux:select.option value="malo">Malo</flux:select.option>
        </flux:select>
        <flux:select label="Dolor al desplazarse" wire:model="assessmentForms.quiropraxia.desplazamiento">
            <flux:select.option value="">Sin especificar</flux:select.option>
            <flux:select.option value="sin_dolor">Sin dolor</flux:select.option>
            <flux:select.option value="ligero">Ligero</flux:select.option>
            <flux:select.option value="moderado">Moderado</flux:select.option>
            <flux:select.option value="severo">Severo</flux:select.option>
        </flux:select>
        <flux:select label="Capacidad de trabajo" wire:model="assessmentForms.quiropraxia.tiempo_trabajo">
            <flux:select.option value="">Sin especificar</flux:select.option>
            <flux:select.option value="tiempo_normal">Tiempo normal</flux:select.option>
            <flux:select.option value="se_extralimita">Se extralimita</flux:select.option>
            <flux:select.option value="no_se_extralimita">No se extralimita</flux:select.option>
            <flux:select.option value="no_puede_trabajar">No puede trabajar</flux:select.option>
        </flux:select>
        <flux:select label="Actividades recreativas" wire:model="assessmentForms.quiropraxia.recreacion">
            <flux:select.option value="">Sin especificar</flux:select.option>
            <flux:select.option value="todas_actividades">Todas las actividades</flux:select.option>
            <flux:select.option value="algunas_actividades">Algunas actividades</flux:select.option>
            <flux:select.option value="pocas_actividades">Pocas actividades</flux:select.option>
            <flux:select.option value="ninguna_actividad">Ninguna actividad</flux:select.option>
        </flux:select>
    </div>
    <div class="grid gap-5 md:grid-cols-2">
        <flux:textarea label="Diagnóstico" wire:model="assessmentForms.quiropraxia.diagnostico" rows="3" />
        <flux:textarea label="Plan inicial de seguimiento" wire:model="assessmentForms.quiropraxia.plan_seguimiento_inicial" rows="3" />
    </div>
    <flux:textarea label="Observaciones de evaluación quiropráctica" wire:model="assessmentForms.quiropraxia.observaciones" rows="3" />
</flux:card>
