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

/** Ficha del paciente con citas, información clínica, documentos y facturación permitidos. */
#[Title('Ficha del paciente')]
class Show extends Component
{
    public Paciente $patient;

    /**
     * @param Paciente $patient
     * @return void
     */
    // Autoriza la consulta de la ficha y carga las relaciones de expediente y paciente fusionador.
    //   Uso:
    //     <livewire:admin.patient.show :patient="$patient" />
    //   Resultado: La ficha muestra el expediente y el vínculo de fusión si existe.
    public function mount(Paciente $patient): void
    {
        $this->authorize('page.view.patients');
        $this->patient = $patient->load(['expediente', 'fusionadoEn']);
    }

    /**
     * @param int $appointmentId
     * @param ConsultationService $consultationService
     * @return void
     */
    // Abre una consulta desde una cita del paciente usando el servicio y confirma la operación con una notificación.
    //   Uso:
    //     wire:click="startConsultation({{ $appointment->id }})"
    //   Resultado: Se crea o abre la consulta asociada y se muestra su número.
    public function startConsultation(int $appointmentId, ConsultationService $consultationService): void
    {
        $this->authorize('consultations.create');

        $consultation = $consultationService->openFromAppointment($appointmentId, $this->patient->id);

        Flux::toast(
            text: "Consulta {$consultation->numero_consulta} abierta correctamente.",
            variant: 'success',
        );
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    // Carga citas paginadas y, según permisos, la información clínica, documentos y facturación vinculados al expediente.
    //   Uso:
    //     Livewire ejecuta render() al mostrar o actualizar la ficha.
    //   Resultado: La vista recibe únicamente los bloques permitidos al usuario.
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
                    ->with(['especialidad', 'profesional', 'anamnesisGeneral', 'evaluacionIntegral'])
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
