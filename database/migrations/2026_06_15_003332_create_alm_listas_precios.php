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
        Schema::create('alm_listas_precios', function (Blueprint $table) {
            $table->id();

            $table->string('codigo')
                ->unique();

            $table->string('nombre');

            $table->string('moneda', 10)
                ->default('BOB');

            $table->boolean('activo')
                ->default(true);

            $table->foreignId('empresa_id')
                ->nullable()                
                ->constrained('conf_empresas')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_listas_precios');
    }
};
