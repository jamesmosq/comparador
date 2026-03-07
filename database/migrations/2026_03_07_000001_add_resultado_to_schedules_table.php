<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('resultado_aprendizaje_id')
                ->nullable()
                ->after('group_id')
                ->constrained('resultados_aprendizaje')
                ->nullOnDelete();

            // Hacer subject nullable para compatibilidad con datos existentes
            $table->string('subject')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['resultado_aprendizaje_id']);
            $table->dropColumn('resultado_aprendizaje_id');
            $table->string('subject')->nullable(false)->change();
        });
    }
};
