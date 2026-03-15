<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropiedadChecksTable extends Migration
{
    public function up(): void
    {
        Schema::create('propiedad_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propiedad_id')->constrained()->onDelete('cascade');
            $table->string('categoria'); // servicios, amenities, caracteristicas
            $table->string('nombre');    // nombre del check
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propiedad_checks');
    }
}
