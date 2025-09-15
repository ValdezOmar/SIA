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
        Schema::create('conf_empresas', function (Blueprint $table) {
            $table->id();
            // Identificación
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('nit', 50)->nullable();
            $table->string('nro_matricula', 50)->nullable();

            // Datos de contacto
            $table->text('direccion')->nullable();
            $table->string('ciudad', 150)->nullable();
            $table->string('pais', 100)->default('Bolivia');
            $table->string('telefono', 50)->nullable();
            $table->string('celular', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('sitio_web', 150)->nullable();
            
            $table->boolean('empresa_activo')->default(1);
                        
            // Auditoría
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conf_sociedades');
    }
};