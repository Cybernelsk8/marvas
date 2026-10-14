<?php

namespace App\Livewire\Admin\Patient;

use App\Models\ArchivoClinico;
use App\Models\Cita;
use App\Models\ConsentimientoRegistro;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\Pago;
use App\Models\PaquetePaciente;
use App\Models\PlanTratamiento;
use App\Services\ConsultationService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ficha del paciente')]
class Show extends Component
{
    public Paciente $patient;

    public function mount(Paciente $patient): void
    {
        $this->authorize('page.view.patients');
        $this->patient = $patient->load(['expediente', 'fusionadoEn']);
    }

    public function startConsultation(int $appointmentId, ConsultationService $consultationService): void
    {
        $this->authorize('consultations.create');

        $consultation = $consultationService->openFromAppointment($appointmentId, $this->patient->id);

        Flux::toast(
            text: "Consulta {$consultation->numero_consulta} abierta correctamente.",
            variant: 'success',
        );
    }

    public function render()
    {
        $expedientId = $this->patient->expediente?->getKey();
        $appointments = Cita::query()
            ->where('expediente_id', $expedientId ?? 0)
            ->with('sucursal')
            ->when(
                auth()->user()->can('consultations.create'),
                fn ($query) => $query->with('consulta:id,cita_id'),
            )
            ->orderByDesc('fecha_hora_inicio')
            ->paginate(10);
        $clinicalRecords = auth()->user()->can('patients.clinical.view') && $expedientId
            ? [
                'consultations' => Consulta::query()
                    ->where('expediente_id', $expedientId)
                    ->with(['especialidad', 'profesional'])
                    ->orderByDesc('fecha_hora')
                    ->limit(20)
                    ->get(),
                'plans' => PlanTratamiento::query()
                    ->where('expediente_id', $expedientId)
                    ->with('clasificacion')
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get(),
            ]
            : null;
        $documents = auth()->user()->can('patients.documents.view') && $expedientId
            ? [
                'files' => ArchivoClinico::query()
                    ->where('expediente_id', $expedientId)
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get(),
                'consents' => ConsentimientoRegistro::query()
                    ->where('expediente_id', $expedientId)
                    ->with(['plantilla', 'profesional.user'])
                    ->orderByDesc('aceptado_at')
                    ->limit(20)
                    ->get(),
            ]
            : null;
        $billing = auth()->user()->can('patients.billing.view') && $expedientId
            ? [
                'packages' => PaquetePaciente::query()
                    ->where('expediente_id', $expedientId)
                    ->with('paquete')
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get(),
                'payments' => Pago::query()
                    ->where('expediente_id', $expedientId)
                    ->orderByDesc('pagado_at')
                    ->limit(20)
                    ->get(),
            ]
            : null;

        return view('livewire.admin.patient.show', compact(
            'appointments',
            'clinicalRecords',
            'documents',
            'billing',
        ));
    }
}
