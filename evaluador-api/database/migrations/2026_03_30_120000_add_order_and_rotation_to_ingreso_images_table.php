<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ingreso_images')) {
            return;
        }

        Schema::table('ingreso_images', function (Blueprint $table) {
            if (!Schema::hasColumn('ingreso_images', 'orden')) {
                $table->unsignedInteger('orden')->default(1)->after('path');
            }

            if (!Schema::hasColumn('ingreso_images', 'rotacion')) {
                $table->smallInteger('rotacion')->default(0)->after('orden');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ingreso_images')) {
            return;
        }

        Schema::table('ingreso_images', function (Blueprint $table) {
            if (Schema::hasColumn('ingreso_images', 'rotacion')) {
                $table->dropColumn('rotacion');
            }

            if (Schema::hasColumn('ingreso_images', 'orden')) {
                $table->dropColumn('orden');
            }
        });
    }
};
