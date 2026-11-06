<?php

namespace App\Livewire\LandingPage;

use App\Models\Sucursal;
use App\Support\ContactLinks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Muestra el directorio público de sedes activas de la clínica.
 *
 * Los enlaces de teléfono, WhatsApp y mapas se preparan en el servidor para
 * que la vista solo se encargue de presentarlos y alternar el mapa visible.
 */
class Sedes extends Component
{
    /**
     * Consulta las sedes activas con sus enlaces públicos ya preparados.
     *
     * @return Collection<int, array<string, mixed>>
     */
    // Consulta las sedes activas con sus enlaces públicos ya preparados.
    //   Ejemplo: desde la vista: @foreach ($this->sedes as $sede)
    //   Resultado: solo aparecen sedes con activa=true, ordenadas por nombre,
    //   y cada una incluye teléfono, WhatsApp, mapa y ruta de llegada.
    #[Computed]
    public function sedes()
    {
        return Sucursal::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Sucursal $sede): array => [
                'id' => $sede->id,
                'nombre' => $sede->nombre,
                'direccion' => $sede->direccion,
                'telefono' => $sede->telefono,
                'telefono_enlace' => ContactLinks::phoneLink($sede->telefono),
                'whatsapp' => ContactLinks::whatsapp($sede->telefono),
                'mapa' => ContactLinks::mapEmbed($sede->direccion),
                'como_llegar' => ContactLinks::directions($sede->direccion),
            ]);
    }

    /**
     * Devuelve la vista pública del directorio de sedes.
     *
     * @return View
     */
    // Devuelve la vista pública del directorio de sedes.
    //   Ejemplo: Livewire lo invoca al montar <livewire:landing-page.sedes />.
    //   Resultado: la sección #contacto se renderiza con las sedes activas y
    //   un enlace general de WhatsApp para el estado vacío.
    public function render()
    {
        return view('livewire.landing-page.sedes', [
            'whatsappGeneral' => ContactLinks::whatsapp(config('marvas.social.whatsapp')),
        ]);
    }
}
