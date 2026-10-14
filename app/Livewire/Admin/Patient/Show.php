<?php

namespace App\Livewire\Admin\Patient;

use App\Models\Cita;
use App\Models\Paciente;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ficha del paciente')]
class Show extends Component
{
    public Paciente $patient;

    public function mount(Paciente $patient): void
    {
        $this->authorize('page.view.patients');
        $this->patient = $patient->load('expediente');
    }

    public function render()
    {
        $appointments = Cita::query()
            ->where('expediente_id', $this->patient->expediente?->getKey() ?? 0)
            ->with('sucursal')
            ->orderByDesc('fecha_hora_inicio')
            ->paginate(10);

        return view('livewire.admin.patient.show', compact('appointments'));
    }
}
