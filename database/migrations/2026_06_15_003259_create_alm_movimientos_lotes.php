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
            $table->id();

            $table->foreignId('movimiento_id')
                ->constrained('alm_movimientos_inventario')
                ->cascadeOnDelete();

            $table->foreignId('lote_id')
                ->constrained('alm_lotes')
                ->cascadeOnDelete();

            $table->decimal('cantidad', 18, 6);

            $table->timestamps();
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
