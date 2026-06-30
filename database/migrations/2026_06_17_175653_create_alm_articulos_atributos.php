<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_articulos_atributos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete()
                ->comment('ID del artículo');

            $table->foreignId('atributo_id')
                ->constrained('alm_atributos')
                ->cascadeOnDelete()
                ->comment('ID del atributo');

            $table->string('valor', 255)
                ->comment('Valor del atributo para este artículo');

            $table->timestamps();

            // Un artículo no puede tener el mismo atributo dos veces
            $table->unique(['articulo_id', 'atributo_id'], 'uk_articulo_atributo');

            // Índices para búsquedas
            $table->index(['articulo_id', 'atributo_id']);
            $table->index('valor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_articulos_atributos');
    }
};