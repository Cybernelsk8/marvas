<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CatalogosClinicosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EspecialidadesSeeder::class,
            CategoriasInventarioSeeder::class,
            SucursalesDemoSeeder::class,
            ProfesionalesDemoSeeder::class,
            EvaluadoresMbiDemoSeeder::class,
            HorariosCitasDemoSeeder::class,
            SolicitudesAccessSeeder::class,
            AgendaAccessSeeder::class,
            PacientesAccessSeeder::class,
            ClinicalCatalogAccessSeeder::class,
        ]);
    }
}
