<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = [
        'unidad_farola_moto',
        'visera',
        'direccionales_moto',
        'manillar',
        'espejo_izq_moto',
        'espejo_der_moto',
        'carenaje_delantero',
        'horquilla',
        'guardafango_frontal',
        'tanque_combustible',
        'sillon',
        'chasis',
        'estribo_moto',
        'tapa_lateral_izq',
        'tapa_lateral_der',
        'tapa_trasera_izq',
        'tapa_trasera_der',
        'guardafango_trasero',
        'stop_moto',
        'pata',
        'caballete',
        'mango_calapie',
        'maleta',
        'cofre_trasero',
        'barra_telescopica_izq',
        'barra_telescopica_der',
        'amortiguador_trasero_moto',
        'motor_moto',
        'kit_arrastre',
        'sistema_escape',
        'bateria_moto',
        'mango_acelerador',
        'manigueta_freno',
        'manigueta_embrague',
        'deposito_liquido_hidraulico',
        'tablero_instrumentos',
        'pedal_freno',
        'pedal_cambios',
        'disco_campana_delantera',
        'disco_campana_trasera',
        'aceite_motor_fugas',
        'combustible_fugas',
        'llanta_delantera_moto',
        'llanta_trasera_moto',
    ];

    public function up(): void
    {
        Schema::table('inspeccion_rev_visual', function (Blueprint $table) {
            foreach ($this->fields as $field) {
                if (!Schema::hasColumn('inspeccion_rev_visual', $field)) {
                    $table->string($field)->nullable();
                }
            }
        });

        Schema::table('inspeccion_rev_visual_punto_liviano', function (Blueprint $table) {
            foreach ($this->fields as $field) {
                if (Schema::hasColumn('inspeccion_rev_visual_punto_liviano', $field)) {
                    $table->dropColumn($field);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspeccion_rev_visual_punto_liviano', function (Blueprint $table) {
            foreach ($this->fields as $field) {
                if (!Schema::hasColumn('inspeccion_rev_visual_punto_liviano', $field)) {
                    $table->string($field)->nullable();
                }
            }
        });

        Schema::table('inspeccion_rev_visual', function (Blueprint $table) {
            foreach ($this->fields as $field) {
                if (Schema::hasColumn('inspeccion_rev_visual', $field)) {
                    $table->dropColumn($field);
                }
            }
        });
    }
};
