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
                'direccion' => null,
                'telefono' => null,
                'activa' => true,
            ],
        );
    }
}
