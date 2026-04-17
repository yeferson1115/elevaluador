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
        Schema::table('avaluos', function (Blueprint $table) {
            $table->date('fecha_inmovilizacion')->nullable()->after('fecha_inspeccion');
            $table->unsignedInteger('dias_inmovilizacion')->nullable()->after('fecha_inmovilizacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avaluos', function (Blueprint $table) {
            $table->dropColumn(['fecha_inmovilizacion', 'dias_inmovilizacion']);
        });
    }
};
