<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_articulos_unidades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete()
                ->comment('ID del artículo');

            $table->foreignId('unidad_medida_id')
                ->constrained('alm_unidades_medida')
                ->cascadeOnDelete()
                ->comment('ID de la unidad de medida');

            $table->decimal('factor_conversion', 18, 6)
                ->default(1)
                ->comment('Factor de conversión a la unidad base');

            $table->boolean('es_compra')
                ->default(false)
                ->comment('Usar esta unidad en compras');

            $table->boolean('es_venta')
                ->default(false)
                ->comment('Usar esta unidad en ventas');

            $table->boolean('es_inventario')
                ->default(false)
                ->comment('Usar esta unidad en inventario');

            $table->timestamps();

            // Un artículo no puede tener la misma unidad dos veces
            $table->unique(['articulo_id', 'unidad_medida_id'], 'uk_articulo_unidad');

            // Índices
            $table->index(['articulo_id', 'unidad_medida_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_articulos_unidades');
    }
};