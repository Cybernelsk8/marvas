<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cierra la dependencia circular anotada en
// 2026_08_10_000019_create_paquetes_paciente_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paquetes_paciente', function (Blueprint $table) {
            $table->foreign('plan_id', 'fk_paqpac_plan')
                ->references('id')->on('planes_tratamiento')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('paquetes_paciente', function (Blueprint $table) {
            $table->dropForeign('fk_paqpac_plan');
        });
    }
};
