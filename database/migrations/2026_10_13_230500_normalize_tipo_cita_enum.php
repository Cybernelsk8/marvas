<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            ALTER TABLE citas
            MODIFY tipo_cita ENUM(
                'primera_vez',
                'primera vez',
                'seguimiento',
                'control',
                'evaluacion_integral',
                'evaluacion integral',
                'procedimiento'
            ) NOT NULL DEFAULT 'seguimiento'
        ");

        DB::table('citas')
            ->where('tipo_cita', 'primera vez')
            ->update(['tipo_cita' => 'primera_vez']);
        DB::table('citas')
            ->where('tipo_cita', 'evaluacion integral')
            ->update(['tipo_cita' => 'evaluacion_integral']);

        DB::statement("
            ALTER TABLE citas
            MODIFY tipo_cita ENUM(
                'primera_vez',
                'seguimiento',
                'control',
                'evaluacion_integral',
                'procedimiento'
            ) NOT NULL DEFAULT 'seguimiento'
        ");
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            ALTER TABLE citas
            MODIFY tipo_cita ENUM(
                'primera_vez',
                'primera vez',
                'seguimiento',
                'control',
                'evaluacion_integral',
                'evaluacion integral',
                'procedimiento'
            ) NOT NULL DEFAULT 'seguimiento'
        ");

        DB::table('citas')
            ->where('tipo_cita', 'primera_vez')
            ->update(['tipo_cita' => 'primera vez']);
        DB::table('citas')
            ->where('tipo_cita', 'evaluacion_integral')
            ->update(['tipo_cita' => 'evaluacion integral']);

        DB::statement("
            ALTER TABLE citas
            MODIFY tipo_cita ENUM(
                'primera vez',
                'seguimiento',
                'control',
                'evaluacion integral',
                'procedimiento'
            ) NOT NULL DEFAULT 'seguimiento'
        ");
    }
};
