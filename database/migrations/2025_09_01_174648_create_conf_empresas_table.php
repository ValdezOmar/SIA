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
            $table->string('nit')->nullable();
            $table->string('nro_matricula')->nullable();
            $table->string('seguro_medico')->nullable();
            // Datos de contacto
            $table->text('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('pais')->default('Bolivia');
            $table->string('telefono')->nullable();
            $table->string('celular')->nullable();
            $table->string('email')->nullable();
            $table->string('sitio_web')->nullable();
            
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
        Schema::dropIfExists('conf_empresas');
    }
};