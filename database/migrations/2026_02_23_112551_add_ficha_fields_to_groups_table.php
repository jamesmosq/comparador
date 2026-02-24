<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('ficha_number')->nullable()->after('name');
            $table->foreignId('programa_formacion_id')->nullable()->after('ficha_number')
                ->constrained('programas_formacion')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['programa_formacion_id']);
            $table->dropColumn(['ficha_number', 'programa_formacion_id']);
        });
    }
};
