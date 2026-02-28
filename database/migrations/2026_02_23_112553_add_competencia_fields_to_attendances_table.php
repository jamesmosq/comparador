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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('competencia_id')->nullable()->after('schedule_id')
                ->constrained('competencias')->nullOnDelete();
            $table->boolean('is_justified')->default(false)->after('is_present');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['competencia_id']);
            $table->dropColumn(['competencia_id', 'is_justified']);
        });
    }
};
