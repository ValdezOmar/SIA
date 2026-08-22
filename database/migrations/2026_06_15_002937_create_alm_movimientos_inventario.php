<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla auxiliar que refleja cada movimiento del libro Kardex.
        Schema::create('alm_movimientos_inventario', function (Blueprint $table) {
            // Identificador del registro operativo.
            $table->id();

            // Artículo y almacén afectados.
            $table->unsignedBigInteger('articulo_id')->nullable();
            $table->unsignedBigInteger('almacen_id')->nullable();

            // Tipo de operación y cantidad; las salidas se guardan negativas.
            $table->string('tipo', 50)->nullable();
            $table->decimal('cantidad', 18, 6)->default(0);
            $table->decimal('costo_unitario', 18, 6)->default(0);
            $table->decimal('costo_total', 18, 6)->default(0);

            // Documento origen para enlazar ventas, compras y ajustes.
            $table->string('documento_tipo', 50)->nullable();
            $table->unsignedBigInteger('documento_id')->nullable();
            $table->string('documento_codigo', 100)->nullable();
            $table->string('tipo_documento', 50)->nullable();
            $table->string('referencia', 100)->nullable();

            // Fechas, observaciones y estado de auditoría.
            $table->timestamp('fecha')->nullable();
            $table->text('observacion')->nullable();
            $table->string('estado', 20)->default('confirmado');

            // Vínculos con Kardex, FIFO, usuarios y movimientos compensatorios.
            $table->unsignedBigInteger('kardex_id')->nullable();
            $table->unsignedBigInteger('capa_costo_id')->nullable();
            $table->unsignedBigInteger('capa_fifo_id')->nullable();
            $table->unsignedBigInteger('movimiento_relacionado_id')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('autorizado_por')->nullable();

            // Auditoría temporal del registro.
            $table->timestamps();

            // Índices con nombres cortos compatibles con MySQL.
            $table->index('articulo_id', 'mov_inv_articulo_idx');
            $table->index('almacen_id', 'mov_inv_almacen_idx');
            $table->index('kardex_id', 'mov_inv_kardex_idx');
            $table->index('movimiento_relacionado_id', 'mov_inv_relacionado_idx');
            $table->index(['documento_tipo', 'documento_id'], 'mov_inv_documento_idx');
            $table->index(['articulo_id', 'almacen_id', 'fecha'], 'mov_inv_historial_idx');
            $table->index('estado', 'mov_inv_estado_idx');
        });
    }

    public function down(): void
    {
        // Elimina la tabla al reconstruir completamente el esquema.
        Schema::dropIfExists('alm_movimientos_inventario');
    }
};
