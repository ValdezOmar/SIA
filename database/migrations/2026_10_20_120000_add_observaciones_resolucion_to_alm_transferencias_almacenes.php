<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alm_transferencias_almacenes', function (Blueprint $table): void {
            $table->text('observaciones_resolucion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('alm_transferencias_almacenes', function (Blueprint $table): void {
            $table->dropColumn('observaciones_resolucion');
        });
    }
};
