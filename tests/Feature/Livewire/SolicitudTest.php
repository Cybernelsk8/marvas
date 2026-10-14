<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Solicitud;
use App\Models\Admin\Setting;
use App\Models\Admin\User;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Sucursal;
use Carbon\CarbonInterface;
use Database\Seeders\CatalogosClinicosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithLivewireHttp;
use Tests\TestCase;

class SolicitudTest extends TestCase
{
    use InteractsWithLivewireHttp;
    use RefreshDatabase;

    private User $recepcionista;

    protected function setUp(): void
    {
        parent::setUp();

        app(CatalogosClinicosSeeder::class)->run();
        Livewire::component('solicitud', Solicitud::class);

        $role = Role::findByName('Recepcionista');

        $this->recepcionista = User::create([
            'email' => 'recepcionista@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);
        $this->recepcionista->assignRole($role);
    }

    public function test_reception_can_save_missing_patient_information_without_overwriting_the_request_message(): void
    {
        $cita = $this->crearSolicitud();
        $paciente = $cita->expediente->paciente;
        $snapshot = $this->componentSnapshot(
            $this->actingAs($this->recepcionista)->get('/solicitudes')->assertOk(),
            'solicitud',
        );
        $snapshot = $this->updatedSnapshot($this->updateLivewireComponent(
            $snapshot,
            calls: [['showSolicitud', [$cita->id]]],
        )->assertOk());

        $this->updateLivewireComponent($snapshot, [
            'solicitud.expediente.paciente.fecha_nacimiento' => '1990-04-12',
            'solicitud.expediente.paciente.numero_dpi' => '1234567890123',
            'solicitud.expediente.paciente.direccion' => 'Zona de demostración',
        ], [['store']])->assertOk();

        $this->assertDatabaseHas('pacientes', [
            'id' => $paciente->id,
            'fecha_nacimiento' => '1990-04-12 00:00:00',
            'numero_dpi' => '1234567890123',
            'direccion' => 'Zona de demostración',
        ]);
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'notas_recepcion' => 'Consulta de primera vez',
            'estado' => 'solicitada',
        ]);
    }

    public function test_reception_can_confirm_a_pending_request_when_its_slot_is_still_free(): void
    {
        $cita = $this->crearSolicitud();
        $page = $this->actingAs($this->recepcionista)->get('/solicitudes')->assertOk()
            ->assertSee('wire:submit="confirmar(', false)
            ->assertSee('wire:model="sucursalConfirmacion"', false);
        $snapshot = $this->componentSnapshot(
            $page,
            'solicitud',
        );

        $snapshot = $this->updatedSnapshot($this->updateLivewireComponent(
            $snapshot,
            calls: [['prepararConfirmacion', [$cita->id]]],
        )->assertOk());

        $this->updateLivewireComponent(
            $snapshot,
            ['sucursalConfirmacion' => (string) Sucursal::query()->where('activa', true)->value('id')],
            calls: [['confirmar', [$cita->id]]],
        )->assertOk();

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'confirmada',
            'sucursal_id' => Sucursal::query()->where('activa', true)->value('id'),
            'agendada_por' => $this->recepcionista->id,
        ]);
    }

    public function test_reception_can_confirm_an_existing_request_without_a_current_schedule_configuration(): void
    {
        Setting::query()->where('key', 'disponibilidad_global_citas')->delete();
        $cita = $this->crearSolicitud();
        $snapshot = $this->componentSnapshot(
            $this->actingAs($this->recepcionista)->get('/solicitudes')->assertOk(),
            'solicitud',
        );
        $snapshot = $this->updatedSnapshot($this->updateLivewireComponent(
            $snapshot,
            calls: [['prepararConfirmacion', [$cita->id]]],
        )->assertOk());

        $this->updateLivewireComponent(
            $snapshot,
            ['sucursalConfirmacion' => (string) Sucursal::query()->where('activa', true)->value('id')],
            calls: [['confirmar', [$cita->id]]],
        )->assertOk();

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'confirmada',
            'sucursal_id' => Sucursal::query()->where('activa', true)->value('id'),
            'agendada_por' => $this->recepcionista->id,
        ]);
    }

    public function test_reception_cannot_confirm_a_request_without_an_active_branch(): void
    {
        $cita = $this->crearSolicitud();
        $snapshot = $this->componentSnapshot(
            $this->actingAs($this->recepcionista)->get('/solicitudes')->assertOk(),
            'solicitud',
        );

        $this->updateLivewireComponent(
            $snapshot,
            calls: [['confirmar', [$cita->id]]],
        )->assertOk();

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'solicitada',
            'sucursal_id' => null,
        ]);
    }

    public function test_reception_cannot_confirm_a_request_using_an_inactive_branch(): void
    {
        $cita = $this->crearSolicitud();
        $sucursal = Sucursal::query()->firstOrFail();
        $sucursal->update(['activa' => false]);
        $snapshot = $this->componentSnapshot(
            $this->actingAs($this->recepcionista)->get('/solicitudes')->assertOk(),
            'solicitud',
        );
        $snapshot = $this->updatedSnapshot($this->updateLivewireComponent(
            $snapshot,
            calls: [['prepararConfirmacion', [$cita->id]]],
        )->assertOk());

        $this->updateLivewireComponent(
            $snapshot,
            ['sucursalConfirmacion' => (string) $sucursal->id],
            calls: [['confirmar', [$cita->id]]],
        )->assertOk();

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'solicitada',
            'sucursal_id' => null,
        ]);
    }

    public function test_reception_can_generate_a_temporary_signed_patient_form_link(): void
    {
        $cita = $this->crearSolicitud();
        $snapshot = $this->componentSnapshot(
            $this->actingAs($this->recepcionista)->get('/solicitudes')->assertOk(),
            'solicitud',
        );

        $response = $this->updateLivewireComponent(
            $snapshot,
            calls: [['generarLinkFormulario', [$cita->id]]],
        )->assertOk();

        $snapshot = json_decode($this->updatedSnapshot($response), true, flags: JSON_THROW_ON_ERROR);
        $url = $snapshot['data']['formularioUrl'];

        $this->assertStringContainsString('/formulario-paciente/'.$cita->id.'?', $url);
        $this->assertTrue(URL::hasValidSignature(Request::create($url)));
    }

    public function test_reception_cannot_confirm_a_request_if_another_active_appointment_overlaps(): void
    {
        $cita = $this->crearSolicitud();
        $this->crearSolicitud('confirmada', $cita->fecha_hora_inicio);
        $snapshot = $this->componentSnapshot(
            $this->actingAs($this->recepcionista)->get('/solicitudes')->assertOk(),
            'solicitud',
        );

        $snapshot = $this->updatedSnapshot($this->updateLivewireComponent(
            $snapshot,
            ['sucursalConfirmacion' => (string) Sucursal::query()->where('activa', true)->value('id')],
        )->assertOk());

        $this->updateLivewireComponent(
            $snapshot,
            calls: [['confirmar', [$cita->id]]],
        )->assertOk();

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'solicitada',
        ]);
    }

    public function test_reception_can_reschedule_a_pending_request_to_an_available_slot(): void
    {
        $cita = $this->crearSolicitud();
        $snapshot = $this->componentSnapshot(
            $this->actingAs($this->recepcionista)->get('/solicitudes')->assertOk(),
            'solicitud',
        );
        $snapshot = $this->updatedSnapshot($this->updateLivewireComponent(
            $snapshot,
            calls: [['prepararReprogramacion', [$cita->id]]],
        )->assertOk());

        $this->updateLivewireComponent($snapshot, [
            'reprogramacion.fecha' => $this->nextWeekday()->addWeek()->format('Y-m-d'),
            'reprogramacion.hora' => '10:00',
        ], [['reprogramar']])->assertOk();

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'solicitada',
            'fecha_hora_inicio' => $this->nextWeekday()->addWeek()->setTime(10, 0)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_reception_can_cancel_a_pending_request_with_a_reason(): void
    {
        $cita = $this->crearSolicitud();
        $snapshot = $this->componentSnapshot(
            $this->actingAs($this->recepcionista)->get('/solicitudes')->assertOk(),
            'solicitud',
        );
        $snapshot = $this->updatedSnapshot($this->updateLivewireComponent(
            $snapshot,
            calls: [['prepararCancelacion', [$cita->id]]],
        )->assertOk());

        $this->updateLivewireComponent($snapshot, [
            'motivoCancelacion' => 'El paciente solicitó cancelar.',
        ], [['cancelar']])->assertOk();

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'cancelada',
            'motivo_cancelacion' => 'El paciente solicitó cancelar.',
        ]);
    }

    public function test_the_requests_page_rejects_users_without_its_view_permission(): void
    {
        $user = User::create([
            'email' => 'sin-permisos@example.test',
            'password' => 'test-password',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/solicitudes')
            ->assertForbidden();
    }

    private function crearSolicitud(string $estado = 'solicitada', ?CarbonInterface $inicio = null): Cita
    {
        $paciente = Paciente::create([
            'nombres' => 'Paciente',
            'apellidos' => 'De prueba',
            'telefono' => '5555-1111',
        ]);
        $expediente = $paciente->expediente()->create();
        $inicio ??= $this->nextWeekday()->setTime(9, 0);

        return $expediente->citas()->create([
            'tipo_cita' => 'primera_vez',
            'estado' => $estado,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addHour(),
            'notas_recepcion' => 'Consulta de primera vez',
        ]);
    }

    private function nextWeekday(): CarbonInterface
    {
        $date = today()->addDay();

        while ($date->isWeekend()) {
            $date->addDay();
        }

        return $date;
    }
}
