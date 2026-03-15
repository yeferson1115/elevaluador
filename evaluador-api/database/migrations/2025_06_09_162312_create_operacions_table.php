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
        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_operacion');
            $table->string('tipo_inmueble');
            $table->string('propiedad_en_cartera')->nullable();
            $table->string('direccion_completa');
            $table->date('fecha_captacion')->nullable();
            $table->date('fecha_reserva')->nullable();
            $table->date('fecha_escrituracion')->nullable();
            $table->boolean('exclusiva')->default(false);
            $table->string('estado_operacion');

            $table->decimal('valor_oferta', 12, 2);
            $table->decimal('porcentaje_pv', 5, 2);
            $table->decimal('porcentaje_pc', 5, 2);
            $table->decimal('porcentaje_honorarios', 5, 2);

            $table->string('tipo_reserva')->nullable();
            $table->decimal('monto_reserva', 12, 2)->nullable();
            $table->string('tipo_refuerzo')->nullable();
            $table->decimal('monto_refuerzo', 12, 2)->nullable();

            $table->unsignedInteger('cantidad_puntas');
            $table->boolean('punta_vendedora')->default(false);
            $table->boolean('punta_compradora')->default(false);

            $table->string('datos_referido')->nullable();
            $table->decimal('porcentaje_referido', 5, 2)->nullable();
            $table->string('datos_compartido')->nullable();
            $table->decimal('porcentaje_compartido', 5, 2)->nullable();

            $table->decimal('porcentaje_franquicia', 5, 2);
            $table->json('asesores');

            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operacions');
    }
};
