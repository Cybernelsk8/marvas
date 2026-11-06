<?php

namespace Tests\Feature\Livewire;

use App\Livewire\LandingPage\Sedes;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LandingSedesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Comprueba que el directorio público muestra solo sedes activas y sus enlaces.
     */
    public function test_branch_directory_lists_only_active_branches_with_contact_links(): void
    {
        Sucursal::create([
            'nombre' => 'Sede Central',
            'direccion' => '18 calle 29-54 zona 12 Colonia Miles Rock',
            'telefono' => '5862-3352',
            'activa' => true,
        ]);

        Sucursal::create([
            'nombre' => 'Sede Cerrada',
            'direccion' => 'Avenida Siempre Viva 123',
            'telefono' => '2222-3333',
            'activa' => false,
        ]);

        Livewire::test(Sedes::class)
            ->assertSee('Sede Central')
            ->assertSee('18 calle 29-54 zona 12 Colonia Miles Rock')
            ->assertSee('5862-3352')
            ->assertSee('tel:+50258623352', false)
            ->assertSee('https://wa.me/50258623352', false)
            ->assertSee('output=embed', false)
            ->assertSee('https://www.google.com/maps/dir/?api=1', false)
            ->assertSee('x-bind:src="\'https', false)
            ->assertDontSee("x-bind:src=''", false)
            ->assertSee('Ver mapa')
            ->assertSee('Cómo llegar')
            ->assertDontSee('Sede Cerrada');
    }

    /**
     * Comprueba que el estado vacío ofrece el WhatsApp general y no inventa sedes.
     */
    public function test_branch_directory_shows_a_whatsapp_fallback_when_there_are_no_active_branches(): void
    {
        config(['marvas.social.whatsapp' => '50258623352']);

        Livewire::test(Sedes::class)
            ->assertSee('Próximamente publicaremos nuestras sedes')
            ->assertSee('https://wa.me/50258623352', false);
    }

    /**
     * Comprueba que el inicio incluye la sección de contacto y el ancla del menú.
     */
    public function test_home_page_includes_the_contact_section_and_anchor(): void
    {
        Sucursal::create([
            'nombre' => 'Sede Central',
            'direccion' => '18 calle 29-54 zona 12 Colonia Miles Rock',
            'telefono' => '5862-3352',
            'activa' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="contacto"', false)
            ->assertSee('Sede Central')
            ->assertSee(route('home').'#contacto', false);
    }

    /**
     * Comprueba que el footer enlaza las redes y abre una conversación de WhatsApp.
     */
    public function test_footer_links_social_profiles_and_whatsapp_conversation(): void
    {
        config([
            'marvas.social.instagram' => 'https://www.instagram.com/marvas_mbi/',
            'marvas.social.facebook' => 'https://www.facebook.com/Cybernelsk8/',
            'marvas.social.whatsapp' => '50258623352',
            'marvas.whatsapp_message' => 'Hola MARVAS',
        ]);

        $html = view('landing-page.partials.foot')->render();

        $this->assertStringContainsString('https://www.instagram.com/marvas_mbi/', $html);
        $this->assertStringContainsString('https://www.facebook.com/Cybernelsk8/', $html);
        $this->assertStringContainsString('https://wa.me/50258623352?text=Hola%20MARVAS', $html);
    }
}
