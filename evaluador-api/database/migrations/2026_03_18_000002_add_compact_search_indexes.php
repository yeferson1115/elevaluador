<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingresos', function (Blueprint $table) {
            $table->index('tiposervicio', 'ingresos_tiposervicio_idx');
            $table->index('placa', 'ingresos_placa_idx');
            $table->index('documento_solicitante', 'ingresos_documento_solicitante_idx');
        });

        Schema::table('avaluos', function (Blueprint $table) {
            $table->index('ingreso_id', 'avaluos_ingreso_id_idx');
            $table->index('user_id', 'avaluos_user_id_idx');
            $table->index('evaluador', 'avaluos_evaluador_idx');
        });
    }

    public function down(): void
    {
        Schema::table('avaluos', function (Blueprint $table) {
            $table->dropIndex('avaluos_ingreso_id_idx');
            $table->dropIndex('avaluos_user_id_idx');
            $table->dropIndex('avaluos_evaluador_idx');
        });

        Schema::table('ingresos', function (Blueprint $table) {
            $table->dropIndex('ingresos_tiposervicio_idx');
            $table->dropIndex('ingresos_placa_idx');
            $table->dropIndex('ingresos_documento_solicitante_idx');
        });
    }
};
