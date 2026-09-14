<?php

namespace App\Livewire\LandingPage;

use App\Models\Paciente;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AgendarCita extends Component
{

    public ?string $tipo = null;
    public ?string $nombres = null;
    public ?string $apellidos = null;
    public ?string $email = null;
    public ?string $telefono = null;
    public ?string $mensaje = null;
    public ?string $fecha = null;
    public ?string $hora = null;

    public function rules()
    {
        return [
            'tipo' => 'required|in:primera_vez,seguimiento',
            'nombres' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s.]+$/u|',
            'apellidos' => 'required|string|max:150|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s.]+$/u|',
            'email' => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'telefono' => 'required|regex:/^\d{4}-\d{4}$/',
            'mensaje' => 'nullable',
            'fecha' => 'required|date|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
        ];
    }

    public function render()
    {
        return view('livewire.landing-page.agendar-cita');
    }

    public function store()
    {

        $this->validate();

        try {
            DB::transaction(function () {
                $paciente = Paciente::create([
                    'nombres' => ucwords($this->nombres),
                    'apellidos' => ucwords($this->apellidos),
                    'telefono' => $this->telefono,
                    'email' => mb_strtolower($this->email),
                ]);

                if (!$paciente) {
                    throw new \Exception('No se pudo crear el paciente');
                }

                $expediente = $paciente->expediente()->create();

                if (!$expediente) {
                    throw new \Exception('No se pudo crear el expediente');
                }

                $cita = $expediente->citas()->create([
                    'tipo' => $this->tipo,
                    'fecha_hora_inicio' => Carbon::parse($this->fecha . ' ' . $this->hora),
                    'fecha_hora_fin' => Carbon::parse($this->fecha . ' ' . $this->hora)->addMinutes(60),
                    'motivo_consulta' => $this->mensaje,
                ]);

                if (!$cita) {
                    throw new \Exception('No se pudo crear la cita');
                }

                Flux::toast(
                    variant: 'success',
                    text: 'Cita agendada correctamente, pronto nos pondremos en contacto contigo.',
                );
            });
        } catch (\Throwable $th) {
            DB::rollBack();
            Flux::toast(
                variant: 'danger',
                text: 'Ocurrió un error al agendar la cita, por favor intenta nuevamente.' . $th->getMessage()
            );
        }
    }
}
