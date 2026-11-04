<div>
    <section class="px-5 py-12 bg-[#F3ECE1]">
        <div class="max-w-3xl mx-auto space-y-6">
            <header class="text-center">
                <h1 class="hero__title">
                    Completa tus <em class="hero__title-highlight">datos</em>
                </h1>
                <p class="mt-4 text-gray-700">
                    Revisa y completa la información para tu expediente clínico.
                </p>
            </header>

            @if ($guardado)
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.heading>Datos guardados</flux:callout.heading>
                    <flux:callout.text>La información se guardó correctamente. Gracias.</flux:callout.text>
                </flux:callout>
            @endif

            <form wire:submit="guardar" class="space-y-5 rounded-xl bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <flux:input label="Nombres *" wire:model="paciente.nombres" required />
                    <flux:input label="Apellidos *" wire:model="paciente.apellidos" required />
                    <flux:input label="Fecha de nacimiento" type="date" wire:model="paciente.fecha_nacimiento" />
                    <flux:select label="Sexo" wire:model="paciente.sexo">
                        <flux:select.option value="">Selecciona una opción</flux:select.option>
                        <flux:select.option value="M">Masculino</flux:select.option>
                        <flux:select.option value="F">Femenino</flux:select.option>
                        <flux:select.option value="otro">Otro</flux:select.option>
                    </flux:select>
                    <flux:select label="Estado civil" wire:model="paciente.estado_civil">
                        <flux:select.option value="">Selecciona una opción</flux:select.option>
                        @foreach (\App\Models\Paciente::estadosCiviles() as $estado)
                            <flux:select.option value="{{ $estado }}">{{ $estado }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input label="DPI" wire:model="paciente.numero_dpi" />
                    <flux:input label="Teléfono" type="tel" mask="9999-9999" maxlength="9" placeholder="0000-0000" wire:model="paciente.telefono" />
                    <flux:input label="Teléfono de emergencia" type="tel" mask="9999-9999" maxlength="9" placeholder="0000-0000" wire:model="paciente.telefono_emergencia" />
                    <flux:input label="Correo electrónico" type="email" wire:model="paciente.email" />
                    <flux:select label="Religión" wire:model="paciente.religion">
                        <flux:select.option value="">Selecciona una opción</flux:select.option>
                        @foreach (\App\Models\Paciente::religiones() as $religion)
                            <flux:select.option value="{{ $religion }}">{{ $religion }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input label="Ocupación" wire:model="paciente.ocupacion" />
                    <flux:input label="Dirección" wire:model="paciente.direccion" />
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Guardar mis datos
                    </flux:button>
                </div>
            </form>
        </div>
    </section>
</div>
