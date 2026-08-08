<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_capas_costos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete();

            $table->foreignId('almacen_id')
                ->constrained('alm_almacenes')
                ->cascadeOnDelete();

            $table->foreignId('kardex_id')
                ->nullable()
                ->constrained('alm_kardex')
                ->nullOnDelete()
                ->comment('Movimiento de kardex que creó esta capa');

            $table->decimal('cantidad_original', 18, 6)
                ->comment('Cantidad original de la capa');

            $table->decimal('cantidad_disponible', 18, 6)
                ->comment('Cantidad aún disponible de esta capa');

            $table->decimal('costo_unitario', 18, 6)
                ->comment('Costo unitario de la capa');

            $table->timestamp('fecha')
                ->useCurrent()
                ->comment('Fecha de creación de la capa');

            $table->boolean('activo')
                ->default(true)
                ->comment('Indica si la capa está activa');

            $table->timestamps();

            $table->index(['articulo_id', 'almacen_id', 'fecha']);
            $table->index(['articulo_id', 'almacen_id', 'cantidad_disponible']);
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_capas_costos');
    }
};