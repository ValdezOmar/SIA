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
        Schema::create('conf_sucursales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')
                ->constrained('conf_empresas')
                ->cascadeOnDelete();

            // Datos de sucursal
            $table->string('nombre', 150);
            $table->text('direccion')->nullable();
            $table->string('ciudad', 150)->nullable();
            $table->string('pais', 100)->nullable();
            $table->string('telefono', 50)->nullable();

            // Configuración
            $table->boolean('activo')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conf_sucursales');
    }
};