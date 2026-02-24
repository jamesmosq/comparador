<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('docente_par_id')->constrained('docentes_par')->restrictOnDelete();
            $table->foreignId('competencia_id')->nullable()->constrained('competencias')->nullOnDelete();
            $table->enum('tipo', ['seguimiento', 'inicio_ficha', 'visita_seguimiento', 'cierre']);
            $table->string('numero_acta');
            $table->date('fecha');
            $table->string('lugar');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->enum('estado', ['borrador', 'finalizada'])->default('borrador');
            $table->text('objetivo')->nullable();
            $table->text('desarrollo')->nullable();
            $table->text('compromisos')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actas');
    }
};
