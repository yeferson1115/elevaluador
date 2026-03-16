<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspeccion_rev_visual_punto_moto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inspeccion_id')->unique();

            $table->string('unidad_farola_moto')->nullable();
            $table->string('visera')->nullable();
            $table->string('direccionales_moto')->nullable();
            $table->string('manillar')->nullable();
            $table->string('espejo_izq_moto')->nullable();
            $table->string('espejo_der_moto')->nullable();
            $table->string('carenaje_delantero')->nullable();
            $table->string('horquilla')->nullable();
            $table->string('guardafango_frontal')->nullable();
            $table->string('tanque_combustible')->nullable();
            $table->string('sillon')->nullable();
            $table->string('chasis')->nullable();
            $table->string('estribo_moto')->nullable();
            $table->string('tapa_lateral_izq')->nullable();
            $table->string('tapa_lateral_der')->nullable();
            $table->string('tapa_trasera_izq')->nullable();
            $table->string('tapa_trasera_der')->nullable();
            $table->string('guardafango_trasero')->nullable();
            $table->string('stop_moto')->nullable();
            $table->string('pata')->nullable();
            $table->string('caballete')->nullable();
            $table->string('mango_calapie')->nullable();
            $table->string('maleta')->nullable();
            $table->string('cofre_trasero')->nullable();
            $table->string('barra_telescopica_izq')->nullable();
            $table->string('barra_telescopica_der')->nullable();
            $table->string('amortiguador_trasero_moto')->nullable();
            $table->string('motor_moto')->nullable();
            $table->string('kit_arrastre')->nullable();
            $table->string('sistema_escape')->nullable();
            $table->string('bateria_moto')->nullable();
            $table->string('mango_acelerador')->nullable();
            $table->string('manigueta_freno')->nullable();
            $table->string('manigueta_embrague')->nullable();
            $table->string('deposito_liquido_hidraulico')->nullable();
            $table->string('tablero_instrumentos')->nullable();
            $table->string('pedal_freno')->nullable();
            $table->string('pedal_cambios')->nullable();
            $table->string('disco_campana_delantera')->nullable();
            $table->string('disco_campana_trasera')->nullable();
            $table->string('aceite_motor_fugas')->nullable();
            $table->string('combustible_fugas')->nullable();
            $table->string('llanta_delantera_moto')->nullable();
            $table->string('llanta_trasera_moto')->nullable();

            $table->timestamps();

            $table->foreign('inspeccion_id')->references('id')->on('inspeccion')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspeccion_rev_visual_punto_moto');
    }
};
