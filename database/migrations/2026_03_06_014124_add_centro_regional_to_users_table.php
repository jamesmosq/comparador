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
        Schema::table('users', function (Blueprint $table) {
            $table->string('centro_formacion')->nullable()->after('role');
            $table->string('regional')->nullable()->after('centro_formacion');
            $table->string('document_number')->nullable()->after('regional');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['centro_formacion', 'regional', 'document_number']);
        });
    }
};
