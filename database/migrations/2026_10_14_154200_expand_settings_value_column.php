<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('value')->change();
        });
    }

    public function down(): void
    {
        if (DB::table('settings')->whereRaw('length(value) > 255')->exists()) {
            throw new \RuntimeException('No se puede reducir settings.value: hay configuraciones que exceden 255 caracteres.');
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->string('value')->change();
        });
    }
};
