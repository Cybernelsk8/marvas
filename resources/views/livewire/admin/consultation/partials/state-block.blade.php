@props(['model', 'catalog', 'estados', 'blank' => false, 'withTest' => false])

{{-- Ítems con selector de estado y detalle. Ej.: model="form.revision_sistemas" --}}
<div class="space-y-3">
    @foreach ($catalog as $key => [$label, $guide])
        <div class="grid gap-3 rounded-lg border border-zinc-200 p-3 md:grid-cols-[14rem_1fr] dark:border-zinc-700">
            <div class="space-y-2">
                <flux:select label="{{ $label }}" wire:model.live="{{ $model }}.{{ $key }}.estado">
                    @if ($blank)
                        <flux:select.option value="">Sin especificar</flux:select.option>
                    @endif
                    @foreach ($estados as $value => $text)
                        <flux:select.option value="{{ $value }}">{{ $text }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($withTest)
                    <flux:select wire:model="{{ $model }}.{{ $key }}.lado" aria-label="Lado de {{ $label }}">
                        <flux:select.option value="">Lado</flux:select.option>
                        @foreach (\App\Support\AnamnesisCatalog::LATERALIDADES as $value => $text)
                            <flux:select.option value="{{ $value }}">{{ $text }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @if ($key === 'fuerza_muscular')
                        <flux:input
                            type="number"
                            min="0"
                            max="5"
                            placeholder="Fuerza 0–5"
                            wire:model="{{ $model }}.{{ $key }}.fuerza"
                        />
                    @endif
                @endif
            </div>
            <flux:textarea
                wire:model="{{ $model }}.{{ $key }}.detalle"
                rows="2"
                placeholder="{{ $guide }}"
                aria-label="Detalle de {{ $label }}"
            />
        </div>
        @error("{$model}.{$key}.estado")
            <flux:error>{{ $message }}</flux:error>
        @enderror
        @error("{$model}.{$key}.fuerza")
            <flux:error>{{ $message }}</flux:error>
        @enderror
    @endforeach
</div>
