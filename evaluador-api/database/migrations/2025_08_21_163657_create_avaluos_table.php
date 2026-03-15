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
    Schema::create('avaluos', function (Blueprint $table) {
        $table->id();

        // Datos Generales
        $table->string('solicitante')->nullable();
        $table->string('documento_solicitante')->nullable();
        $table->string('placa')->nullable();
        $table->string('ubicacion_activo')->nullable();
        $table->date('fecha_solicitud')->nullable();
        $table->date('fecha_inspeccion')->nullable();
        $table->date('fecha_informe')->nullable();
        $table->string('objeto_avaluo')->nullable();
        $table->string('codigo_interno_movil')->nullable();

        // Información del Bien
        $table->string('tipo_propiedad')->nullable();
        $table->date('fecha_matricula')->nullable();
        $table->string('movil')->nullable();
        $table->string('marca')->nullable();
        $table->string('linea')->nullable();
        $table->string('clase')->nullable();
        $table->string('tipo_carroceria')->nullable();
        $table->string('categoria')->nullable();
        $table->string('color')->nullable();
        $table->integer('cilindraje')->nullable();
        $table->integer('modelo')->nullable();
        $table->integer('kilometraje')->nullable();
        $table->string('caja_cambios')->nullable();
        $table->string('tipo_traccion')->nullable();
        $table->integer('numero_pasajeros')->nullable();
        $table->integer('capacidad_carga')->nullable();
        $table->string('llanta_delantera_izquierda')->nullable();
        $table->string('llanta_delantera_derecha')->nullable();
        $table->string('llanta_trasera_izquierda')->nullable();
        $table->string('llanta_trasera_derecha')->nullable();
        $table->string('llanta_repuesto')->nullable();
        $table->string('numero_chasis')->nullable();
        $table->string('numero_serie')->nullable();
        $table->string('numero_motor')->nullable();
        $table->string('nacionalidad')->nullable();
        $table->string('propietario')->nullable();
        $table->string('empresa_afiliacion')->nullable();
        $table->string('soat')->nullable();
        $table->string('rtm')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaluos');
    }
};
