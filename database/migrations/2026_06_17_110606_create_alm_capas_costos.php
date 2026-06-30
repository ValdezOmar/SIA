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
        Schema::create('alm_capas_costos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos');

            $table->foreignId('almacen_id')
                ->constrained('alm_almacenes');

            $table->foreignId('movimiento_id')
                ->nullable()
                ->constrained('alm_movimientos_inventario')
                ->nullOnDelete();

            $table->decimal('cantidad_original', 18, 6);

            $table->decimal('cantidad_disponible', 18, 6);

            $table->decimal('costo_unitario', 18, 6);

            $table->timestamp('fecha');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_capas_costos');
    }
};
