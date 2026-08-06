<?php

namespace App\Livewire\LandingPage;

use Livewire\Component;

class AgendarCita extends Component
{
    public ?array $cita = [
        'tipo' => null,
        'nombre' => null,
        'email' => null,
        'telefono' => null,
        'mensaje' => null,
        'fecha' => null,
        'hora' => null,
    ];
    public function render()
    {
        return view('livewire.landing-page.agendar-cita');
    }
}
