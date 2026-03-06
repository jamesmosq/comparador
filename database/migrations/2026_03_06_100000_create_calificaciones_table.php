<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resultado_aprendizaje_id')->constrained('resultados_aprendizaje')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->decimal('nota', 3, 1)->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['student_id', 'resultado_aprendizaje_id', 'group_id'],
                'calificacion_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
