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
            $table->boolean('cerrado')->default(false)->after('inicial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avaluos', function (Blueprint $table) {
            $table->dropColumn('cerrado');
        });
    }
};
