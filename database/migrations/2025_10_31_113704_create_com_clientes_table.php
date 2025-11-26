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
        Schema::create('com_clientes', function (Blueprint $table) {
            $table->id();            
            $table->string('razon_social');
            $table->string('ci_nit', 50)->nullable();
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            $table->string('tipo_institucion')->nullable(); //tipo estataes, privados, etc
            $table->string('direccion')->nullable();            
            $table->string('ciudad', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * 
     */
    public function down(): void
    {
        Schema::dropIfExists('com_clientes');
    }
};