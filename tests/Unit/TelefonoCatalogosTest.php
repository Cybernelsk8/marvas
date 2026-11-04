<?php

namespace Tests\Unit;

use App\Models\Paciente;
use App\Models\Sucursal;
use PHPUnit\Framework\TestCase;

class TelefonoCatalogosTest extends TestCase
{
    public function test_telefono_se_normaliza_a_formato_con_guion(): void
    {
        $paciente = new Paciente(['telefono' => '48840150', 'telefono_emergencia' => ' 4884-0150 ']);

        $this->assertSame('4884-0150', $paciente->telefono);
        $this->assertSame('4884-0150', $paciente->telefono_emergencia);
        $this->assertSame('4884-0150', (new Sucursal(['telefono' => '48840150']))->telefono);
    }

    public function test_telefono_vacio_queda_nulo(): void
    {
        $this->assertNull((new Paciente(['telefono' => '']))->telefono);
    }

    public function test_regla_telefono_acepta_solo_8_digitos(): void
    {
        $regla = Paciente::REGLA_TELEFONO;
        $patron = substr($regla, strlen('regex:'));

        $this->assertSame(1, preg_match($patron, '4884-0150'));
        $this->assertSame(1, preg_match($patron, '48840150'));
        $this->assertSame(0, preg_match($patron, '488401'));
        $this->assertSame(0, preg_match($patron, '488401501'));
    }

    public function test_catalogos_son_listas_de_textos(): void
    {
        $this->assertContains('Soltero', Paciente::estadosCiviles());
        $this->assertContains('Católico', Paciente::religiones());
    }
}
