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
        Schema::create('alm_movimientos_lotes', function (Blueprint $table) {
            // Identificador del movimiento de lote.
            $table->id();

            // Referencias al movimiento auxiliar y al lote afectado.
            $table->unsignedBigInteger('movimiento_id');
            $table->unsignedBigInteger('lote_id');

            // Cantidad positiva en entradas y negativa en salidas.
            $table->decimal('cantidad', 18, 6)->default(0);

            // Auditoría temporal.

            $table->timestamps();

            // Consultas de trazabilidad por movimiento y lote.
            $table->index('movimiento_id', 'mov_lote_mov_idx');
            $table->index('lote_id', 'mov_lote_lote_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_movimientos_lotes');
    }
};
