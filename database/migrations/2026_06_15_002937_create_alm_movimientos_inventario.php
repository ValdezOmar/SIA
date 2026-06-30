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
        Schema::create('alm_movimientos_inventario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos');

            $table->foreignId('almacen_id')
                ->constrained('alm_almacenes');

            $table->enum('tipo', [
                'entrada_compra',
                'salida_venta',
                'ajuste_positivo',
                'ajuste_negativo',
                'transferencia_entrada',
                'transferencia_salida',
                'produccion_entrada',
                'produccion_salida'
            ]);

            $table->decimal('cantidad', 18, 6);

            $table->decimal('costo_unitario', 18, 6)
                ->default(0);

            $table->decimal('costo_total', 18, 6)
                ->default(0);

            $table->string('documento_tipo');

            $table->unsignedBigInteger('documento_id');

            $table->timestamp('fecha');

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->index([
                'articulo_id',
                'almacen_id',
                'fecha'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_movimientos_inventario');
    }
};
