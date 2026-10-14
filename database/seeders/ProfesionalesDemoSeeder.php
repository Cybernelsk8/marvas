<?php

namespace Database\Seeders;

use App\Models\Admin\User;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProfesionalesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $sucursal = Sucursal::query()
            ->where('nombre', 'Sede de demostración MARVAS')
            ->first();

        if (! $sucursal) {
            return;
        }

        foreach (Especialidad::query()->where('activa', true)->get() as $especialidad) {
            $email = "demo.{$especialidad->slug}@example.test";

            $usuario = User::withTrashed()->firstOrCreate(
                ['email' => $email],
                ['password' => Str::random(64)],
            );

            $profesional = Profesional::updateOrCreate(
                ['user_id' => $usuario->id],
                [
                    'nombre_completo' => "Profesional de demostración - {$especialidad->nombre}",
                    'cedula_profesional' => null,
                    'color_agenda_hex' => $especialidad->color_hex,
                    'activo' => true,
                ],
            );

            $profesional->especialidades()->syncWithoutDetaching([
                $especialidad->id => ['es_principal' => true],
            ]);
            $profesional->sucursales()->syncWithoutDetaching([
                $sucursal->id => ['es_principal' => true],
            ]);
        }
    }
}
