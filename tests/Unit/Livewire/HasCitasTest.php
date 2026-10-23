<?php

namespace Tests\Unit\Livewire;

use App\Livewire\Traits\HasCitas;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class HasCitasTest extends TestCase
{
    public function test_it_returns_calendar_events(): void
    {
        $component = new class
        {
            use HasCitas;
        };

        $firstAppointment = $this->appointment('2026-10-13 09:00', '2026-10-13 10:00');

        $this->assertSame([
            '2026-10-13' => [[
                'title' => 'Ocupado',
                'time' => '09:00',
                'color' => '#dc2626',
            ]],
        ], $component->getEvents([$firstAppointment]));
    }

    public function test_it_removes_overlapping_hours_from_availability(): void
    {
        Carbon::setTestNow('2026-10-13 08:00:00');
        $component = new class
        {
            use HasCitas;
        };

        $firstAppointment = $this->appointment('2026-10-13 09:00', '2026-10-13 10:00');
        $secondAppointment = $this->appointment('2026-10-13 10:00', '2026-10-13 11:00');

        $this->assertSame(
            ['11:00'],
            $component->getAvailableHours('2026-10-13', ['09:00', '10:00', '11:00'], [$firstAppointment, $secondAppointment]),
        );
        Carbon::setTestNow();
    }

    public function test_it_excludes_the_current_appointment_and_only_offers_future_start_times(): void
    {
        Carbon::setTestNow('2026-10-13 09:30:00');
        $component = new class
        {
            use HasCitas;
        };
        $appointment = $this->appointment('2026-10-13 10:00', '2026-10-13 11:00', 42);

        $this->assertSame(
            ['10:00', '11:00'],
            $component->getAvailableHours(
                '2026-10-13',
                ['09:00', '10:00', '11:00'],
                [$appointment],
                ignoreAppointmentId: 42,
            ),
        );

        Carbon::setTestNow();
    }

    public function test_it_returns_fully_booked_dates(): void
    {
        $component = new class
        {
            use HasCitas;
        };

        $firstAppointment = $this->appointment('2026-10-13 09:00', '2026-10-13 10:00');
        $secondAppointment = $this->appointment('2026-10-13 10:00', '2026-10-13 11:00');

        $fecha = Carbon::parse('2026-10-13');

        $this->assertSame(
            ['2026-10-13'],
            $component->getFechasLlenas(
                ['09:00', '10:00'],
                [$firstAppointment, $secondAppointment],
                $fecha,
                $fecha,
            ),
        );
    }

    private function appointment(string $start, string $end, ?int $id = null): object
    {
        return (object) [
            'id' => $id,
            'fecha_hora_inicio' => CarbonImmutable::parse($start),
            'fecha_hora_fin' => CarbonImmutable::parse($end),
        ];
    }
}
