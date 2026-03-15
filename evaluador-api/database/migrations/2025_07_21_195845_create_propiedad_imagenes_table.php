<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropiedadImagenesTable extends Migration
{
    public function up(): void
    {
        Schema::create('propiedad_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propiedad_id')->constrained()->onDelete('cascade');
            $table->string('ruta'); // nombre o path del archivo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propiedad_imagenes');
    }
}

