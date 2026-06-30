<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_codigos_barras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete()
                ->comment('ID del artículo');

            $table->string('codigo_barras', 100)
                ->unique()
                ->comment('Código de barras');

            $table->string('tipo', 50)
                ->nullable()
                ->comment('Tipo de código: EAN-13, EAN-8, UPC-A, etc.');

            $table->boolean('principal')
                ->default(false)
                ->comment('Indica si es el código principal del artículo');

            $table->string('descripcion', 255)
                ->nullable()
                ->comment('Descripción adicional del código');

            $table->timestamps();

            // Índices
            $table->index('tipo');
            $table->index('principal');
            $table->index(['articulo_id', 'principal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_codigos_barras');
    }
};