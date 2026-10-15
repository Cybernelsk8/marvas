<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anamnesis_generales', function (Blueprint $table): void {
            $table->text('motivo_consulta')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('anamnesis_generales')
            ->whereNull('motivo_consulta')
            ->update(['motivo_consulta' => '']);

        Schema::table('anamnesis_generales', function (Blueprint $table): void {
            $table->text('motivo_consulta')->nullable(false)->change();
        });
    }
};
