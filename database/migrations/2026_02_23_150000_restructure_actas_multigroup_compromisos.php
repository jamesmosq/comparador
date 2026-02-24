<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hacer group_id nullable en actas (usaremos pivot para múltiples fichas)
        DB::statement('ALTER TABLE actas DROP FOREIGN KEY actas_group_id_foreign');
        DB::statement('ALTER TABLE actas MODIFY COLUMN group_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE actas ADD CONSTRAINT actas_group_id_foreign FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE SET NULL');

        // 2. Agregar columna agenda
        Schema::table('actas', function (Blueprint $table) {
            $table->text('agenda')->nullable()->after('lugar');
        });

        // 3. Agregar nuevo valor al enum tipo
        DB::statement("ALTER TABLE actas MODIFY COLUMN tipo ENUM('seguimiento','inicio_ficha','visita_seguimiento','cierre','aprobacion_etapa_practica') NOT NULL");

        // 4. Tabla pivote actas ↔ grupos (muchos a muchos)
        Schema::create('acta_group', function (Blueprint $table) {
            $table->foreignId('acta_id')->constrained('actas')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->primary(['acta_id', 'group_id']);
        });

        // 5. Tabla de compromisos estructurados
        Schema::create('acta_compromisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acta_id')->constrained('actas')->cascadeOnDelete();
            $table->text('descripcion');
            $table->enum('responsable', ['instructor_sena', 'docente_par', 'ambos'])->default('ambos');
            $table->date('fecha_limite')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acta_compromisos');
        Schema::dropIfExists('acta_group');

        Schema::table('actas', function (Blueprint $table) {
            $table->dropColumn('agenda');
        });

        DB::statement("ALTER TABLE actas MODIFY COLUMN tipo ENUM('seguimiento','inicio_ficha','visita_seguimiento','cierre') NOT NULL");

        DB::statement('ALTER TABLE actas DROP FOREIGN KEY actas_group_id_foreign');
        DB::statement('ALTER TABLE actas MODIFY COLUMN group_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE actas ADD CONSTRAINT actas_group_id_foreign FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE');
    }
};
