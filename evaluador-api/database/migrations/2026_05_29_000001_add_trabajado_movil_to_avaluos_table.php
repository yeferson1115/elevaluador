<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avaluos', function (Blueprint $table) {
            $table->boolean('trabajado_movil')->default(false)->after('cerrado');
            $table->index(['trabajado_movil', 'user_id'], 'avaluos_trabajado_movil_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('avaluos', function (Blueprint $table) {
            $table->dropIndex('avaluos_trabajado_movil_user_id_idx');
            $table->dropColumn('trabajado_movil');
        });
    }
};
