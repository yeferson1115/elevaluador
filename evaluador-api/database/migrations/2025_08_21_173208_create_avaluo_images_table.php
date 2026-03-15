<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void {
        Schema::create('avaluo_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('avaluo_id');
            $table->string('categoria');
            $table->string('path'); // ruta de la imagen en storage
            $table->timestamps();

            $table->foreign('avaluo_id')->references('id')->on('avaluos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaluo_images');
    }
};
