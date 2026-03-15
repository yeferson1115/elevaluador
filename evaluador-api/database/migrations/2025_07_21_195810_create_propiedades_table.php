<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropiedadesTable extends Migration
{
    public function up(): void
    {
        Schema::create('propiedades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->string('tipo_operacion');
            $table->string('tipo_propiedad');
            $table->decimal('precio', 15, 2);
            $table->string('moneda');
            $table->decimal('comision_vendedor', 5, 2);
            $table->decimal('comision_comprador', 5, 2);
            $table->date('inicio_autorizacion');
            $table->date('fin_autorizacion');
            $table->decimal('expensas', 15, 2)->nullable();
            $table->string('moneda_expensas')->nullable();

            // Ubicación
            $table->string('calle');
            $table->string('numero');
            $table->string('barrio');
            $table->string('ciudad');
            $table->string('provincia');
            $table->string('pais');
            $table->string('link_google_maps')->nullable();

            // Características físicas
            $table->string('estado_propiedad')->nullable();
            $table->tinyInteger('ambientes')->nullable();
            $table->tinyInteger('dormitorios')->nullable();
            $table->tinyInteger('banos')->nullable();
            $table->tinyInteger('antiguedad')->nullable();
            $table->string('orientacion')->nullable();
            $table->decimal('sup_cubierta', 10, 2)->nullable();
            $table->decimal('sup_semi_cubierta', 10, 2)->nullable();
            $table->decimal('sup_descubierta', 10, 2)->nullable();

            // Publicación
            $table->string('titulo_publicacion');
            $table->text('descripcion_detallada');

            // Multimedia
            $table->string('link_youtube')->nullable();
            $table->string('link_tour_360')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propiedades');
    }
}

