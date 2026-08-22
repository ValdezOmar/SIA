<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_capas_costos', function (Blueprint $table) {
            // Identificador interno de la capa FIFO.
            $table->id();

            // Artículo y almacén al que pertenece el costo.
            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete();

            $table->foreignId('almacen_id')
                ->constrained('alm_almacenes')
                ->cascadeOnDelete();

            // Kardex que originó la capa. Se deja sin FK porque esta migración
            // se ejecuta antes de crear alm_kardex y existe dependencia circular.
            $table->unsignedBigInteger('kardex_id')
                ->nullable()
                ->comment('Movimiento de kardex que creó esta capa');

            // Cantidad recibida y saldo aún disponible para consumo FIFO.
            $table->decimal('cantidad_original', 18, 6)
                ->comment('Cantidad original de la capa');

            $table->decimal('cantidad_disponible', 18, 6)
                ->comment('Cantidad aún disponible de esta capa');

            // Costo unitario utilizado para valorar las existencias.
            $table->decimal('costo_unitario', 18, 6)
                ->comment('Costo unitario de la capa');

            // Fecha usada para ordenar las capas en el consumo FIFO.
            $table->timestamp('fecha')
                ->useCurrent()
                ->comment('Fecha de creación de la capa');

            // Permite excluir capas sin eliminar el historial.
            $table->boolean('activo')
                ->default(true)
                ->comment('Indica si la capa está activa');

            // Fechas de auditoría de Laravel.
            $table->timestamps();

            // Índices para búsquedas FIFO y filtros por artículo/almacén.
            $table->index(['articulo_id', 'almacen_id', 'fecha'], 'capas_art_alm_fecha_idx');
            $table->index(['articulo_id', 'almacen_id', 'cantidad_disponible'], 'capas_art_alm_disp_idx');
            $table->index('kardex_id', 'capas_kardex_idx');
            $table->index('activo', 'capas_activo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_capas_costos');
    }
};