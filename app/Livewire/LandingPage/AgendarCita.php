<?php

namespace App\Livewire\LandingPage;

use App\Livewire\Traits\HasCitas;
use App\Models\Cita;
use App\Models\Paciente;
use App\Services\ClinicAvailabilityService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AgendarCita extends Component
{
    use HasCitas;

    public ?string $nombres = null;

    public ?string $apellidos = null;

    public ?string $email = null;

    public ?string $telefono = null;

    public ?string $mensaje = null;

    public function rules(): array
    {
        return [
            'nombres' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s.]+$/u',
            'apellidos' => 'required|string|max:150|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s.]+$/u',
            'telefono' => 'required|regex:/^\d{4}-\d{4}$/',
            'email' => 'nullable|email|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'mensaje' => 'nullable',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
        ];
    }

    public function updatedFecha(): void
    {
        if ($this->fecha && $this->hora && ! in_array(
            $this->hora,
            $this->getAvailableHours($this->fecha, availabilityConfiguration: app(ClinicAvailabilityService::class)->configuration()),
            true,
        )) {
            $this->reset('hora');
        }
    }

    public function render()
    {
        $availability = app(ClinicAvailabilityService::class);
        $configuration = $availability->configuration();
        $horariosConfigurados = $availability->recurringHours($configuration);
        $citas = $this->getCalendarAppointments();
        $eventos = $this->getEvents($citas);
        $fechasLlenas = $this->getFechasLlenas(
            $horariosConfigurados,
            $citas,
            availabilityConfiguration: $configuration,
        );

        $horarios = $this->fecha
            ? $this->getAvailableHours($this->fecha, $horariosConfigurados, $citas, $configuration)
            : [];

        return view('livewire.landing-page.agendar-cita', compact('horarios', 'eventos', 'fechasLlenas'));
    }

    public function store(): void
    {
        $datos = $this->validate();

        $inicio = Carbon::parse("{$datos['fecha']} {$datos['hora']}");

        $resultado = DB::transaction(function () use ($datos, $inicio): string {
            if (! $inicio->isFuture()) {
                return 'fecha_pasada';
            }

            $availability = app(ClinicAvailabilityService::class);
            $configuration = $availability->configuration(lockForUpdate: true);

            $durationMinutes = $configuration['duration_minutes'];
            if (! $availability->isSlotAllowed($inicio, $durationMinutes, $configuration)) {
                return 'horario_no_configurado';
            }

            $fin = $inicio->copy()->addMinutes($durationMinutes);

            if (Cita::existeTraslapeGlobal($inicio, $fin)) {
                return 'horario_ocupado';
            }
            $paciente = Paciente::create([
                'nombres' => ucwords(mb_strtolower($datos['nombres'])),
                'apellidos' => ucwords(mb_strtolower($datos['apellidos'])),
                'telefono' => $datos['telefono'],
                'email' => filled($datos['email']) ? mb_strtolower($datos['email']) : null,
            ]);

            $expediente = $paciente->expediente()->create();
            $expediente->citas()->create([
                'tipo_cita' => 'primera_vez',
                'estado' => 'solicitada',
                'fecha_hora_inicio' => $inicio,
                'fecha_hora_fin' => $fin,
                'duracion_minutos' => $durationMinutes,
                'notas_recepcion' => $datos['mensaje'],
            ]);

            return 'solicitada';
        });

        if ($resultado !== 'solicitada') {
            $this->addError(
                'hora',
                match ($resultado) {
                    'fecha_pasada' => 'La fecha y hora seleccionadas deben estar en el futuro.',
                    'horario_no_configurado' => 'El horario seleccionado ya no está disponible. Elige otro horario.',
                    default => 'Ese horario acaba de ocuparse. Elige otro horario.',
                },
            );

            return;
        }

        $this->resetData();

        Flux::toast(
            variant: 'success',
            text: 'Solicitud recibida correctamente. Recepción se pondrá en contacto contigo para confirmar la cita.',
        );
    }

    private function resetData(): void
    {
        $this->reset([
            'nombres',
            'apellidos',
            'email',
            'telefono',
            'mensaje',
            'fecha',
            'hora',
        ]);
    }
}
