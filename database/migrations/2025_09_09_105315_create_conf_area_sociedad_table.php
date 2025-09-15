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
        Schema::create('conf_area_empresa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('conf_areas')->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('conf_empresas')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conf_area_empresa');
    }
};