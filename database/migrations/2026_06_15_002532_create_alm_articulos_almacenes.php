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
        Schema::create('alm_articulos_almacenes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete();

            $table->foreignId('almacen_id')
                ->constrained('alm_almacenes')
                ->cascadeOnDelete();

            $table->decimal('stock_actual', 18, 6)
                ->default(0);

            $table->decimal('stock_comprometido', 18, 6)
                ->default(0);

            $table->decimal('stock_pedido', 18, 6)
                ->default(0);

            $table->decimal('stock_minimo', 18, 6)
                ->default(0);

            $table->decimal('stock_maximo', 18, 6)
                ->default(0);

            $table->decimal('costo_promedio', 18, 6)
                ->default(0);

            $table->timestamps();

            $table->unique([
                'articulo_id',
                'almacen_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_articulos_almacenes');
    }
};
