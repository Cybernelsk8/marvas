<?php

namespace App\Livewire\LandingPage;

use App\Livewire\Traits\HasCitas;
use App\Models\Paciente;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AgendarCita extends Component
{
    use HasCitas;

    public ?string $tipo = null;
    public ?string $nombres = null;
    public ?string $apellidos = null;
    public ?string $email = null;
    public ?string $telefono = null;
    public ?string $mensaje = null;

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:primera_vez,seguimiento',
            'nombres' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s.]+$/u',
            'apellidos' => 'required|string|max:150|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s.]+$/u',
            'telefono' => 'required|regex:/^\d{4}-\d{4}$/',
            'email' => 'nullable|email|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'mensaje' => 'nullable',
            'fecha' => 'required|date|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
        ];
    }

    public function updatedFecha(): void
    {
        if ($this->fecha && $this->hora && ! in_array($this->hora, $this->getAvailableHours($this->fecha), true)) {
            $this->reset('hora');
        }
    }

    public function render()
    {
        $horariosConfigurados = $this->getConfiguredHours();
        $citas = $this->getCalendarAppointments();
        $eventos = $this->getEvents($citas);
        $fechasLlenas = $this->getFechasLlenas($horariosConfigurados, $citas);

        $horarios = $this->fecha ? $this->getAvailableHours($this->fecha, $horariosConfigurados, $citas) : [];

        return view('livewire.landing-page.agendar-cita', compact('horarios', 'eventos', 'fechasLlenas'));
    }

    public function store(): void
    {
        $datos = $this->validate();

        if (! in_array($datos['hora'], $this->getAvailableHours($datos['fecha']), true)) {
            $this->addError('hora', 'El horario seleccionado ya no está disponible. Elige otro horario.');

            return;
        }

        $inicio = Carbon::parse("{$datos['fecha']} {$datos['hora']}");

        try {
            DB::transaction(function () use ($datos, $inicio) {
                $paciente = Paciente::create([
                    'nombres' => ucwords(mb_strtolower($datos['nombres'])),
                    'apellidos' => ucwords(mb_strtolower($datos['apellidos'])),
                    'telefono' => $datos['telefono'],
                    'email' => filled($datos['email']) ? mb_strtolower($datos['email']) : null,
                ]);

                $expediente = $paciente->expediente()->create();
                $expediente->citas()->create([
                    'tipo_cita' => $datos['tipo'],
                    'fecha_hora_inicio' => $inicio,
                    'fecha_hora_fin' => $inicio->copy()->addMinutes(60),
                    'notas_recepcion' => $datos['mensaje'],
                ]);
            });
        } catch (\Throwable $exception) {
            report($exception);

            Flux::toast(
                variant: 'danger',
                text: 'Ocurrió un error al agendar la cita, por favor intenta nuevamente.',
            );

            return;
        }

        $this->resetData();

        Flux::toast(
            variant: 'success',
            text: 'Cita agendada correctamente, pronto nos pondremos en contacto contigo.',
        );
    }

    private function resetData(): void
    {
        $this->reset([
            'tipo',
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
