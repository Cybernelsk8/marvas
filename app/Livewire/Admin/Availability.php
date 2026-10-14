<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Setting;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Disponibilidad')]
class Availability extends Component
{
    public array $hours = [];

    public function mount(): void
    {
        $this->authorize('page.view.schedule');

        $hours = Setting::query()
            ->where('key', 'horarios_citas')
            ->first()?->value;
        $this->hours = is_array($hours) ? array_values($hours) : [];
    }

    public function render()
    {
        return view('livewire.admin.availability');
    }

    public function addHour(): void
    {
        $this->authorize('schedule.edit');

        $this->hours[] = '';
    }

    public function removeHour(int $index): void
    {
        $this->authorize('schedule.edit');

        if (array_key_exists($index, $this->hours)) {
            unset($this->hours[$index]);
            $this->hours = array_values($this->hours);
        }
    }

    public function save(): void
    {
        $this->authorize('schedule.edit');

        $validated = $this->validate([
            'hours' => ['required', 'array', 'min:1', 'max:48'],
            'hours.*' => ['required', 'date_format:H:i', 'distinct'],
        ]);
        $hours = collect($validated['hours'])->sort()->values()->all();

        DB::transaction(function () use ($hours): void {
            $setting = Setting::query()
                ->where('key', 'horarios_citas')
                ->lockForUpdate()
                ->first();

            if ($setting === null) {
                $setting = new Setting(['key' => 'horarios_citas']);
            }

            $setting->fill([
                'type' => 'array',
                'value' => $hours,
                'description' => 'Horas de inicio disponibles globalmente para las citas de la clínica.',
            ]);
            $setting->save();
        });

        $this->hours = $hours;
        $this->resetValidation();
        Flux::toast(text: 'Los horarios de atención se guardaron correctamente.', variant: 'success');
    }
}
