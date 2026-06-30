<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_atributos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)
                ->unique()
                ->comment('Código único del atributo');

            $table->string('nombre', 100)
                ->comment('Nombre del atributo (ej: Talla, Color, Material)');

            $table->string('descripcion', 255)
                ->nullable()
                ->comment('Descripción del atributo');

            $table->boolean('activo')
                ->default(true)
                ->comment('Indica si el atributo está activo');

            $table->timestamps();

            // Índices
            $table->index('activo');
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_atributos');
    }
};