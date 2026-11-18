<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        Sucursal::updateOrCreate(
            ['nombre' => 'Sede de demostración MARVAS'],
            [
                'direccion' => '18 calle 29-54 zona 12 Colonia Miles Rock',
                'telefono' => '58623352',
                'activa' => true,
            ],
        );
    }
}
