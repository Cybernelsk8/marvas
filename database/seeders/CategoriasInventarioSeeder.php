<?php

namespace Database\Seeders;

use App\Models\CategoriaInventario;
use Illuminate\Database\Seeder;

class CategoriasInventarioSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Medicamentos', 'slug' => 'medicamentos', 'tipo' => 'medicamento'],
            ['nombre' => 'Remedios homeopáticos', 'slug' => 'remedios-homeopaticos', 'tipo' => 'remedio_homeopatico'],
            ['nombre' => 'Insumos clínicos', 'slug' => 'insumos-clinicos', 'tipo' => 'insumo_clinico'],
            ['nombre' => 'Equipo', 'slug' => 'equipo', 'tipo' => 'equipo'],
            ['nombre' => 'Suplementos', 'slug' => 'suplementos', 'tipo' => 'suplemento'],
            ['nombre' => 'Otros', 'slug' => 'otros', 'tipo' => 'otro'],
        ];

        foreach ($categorias as $categoria) {
            CategoriaInventario::withoutTimestamps(
                fn () => CategoriaInventario::updateOrCreate(
                    ['slug' => $categoria['slug']],
                    [...$categoria, 'descripcion' => null, 'activa' => true],
                ),
            );
        }
    }
}
