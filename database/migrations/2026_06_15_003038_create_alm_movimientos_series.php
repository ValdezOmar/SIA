<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_movimientos_series', function (Blueprint $table) {
            $table->id();

            $table->foreignId('serie_id')
                ->constrained('alm_series')
                ->cascadeOnDelete();

            $table->foreignId('movimiento_inventario_id')
                ->constrained('alm_movimientos_inventario')
                ->cascadeOnDelete();

            $table->enum('tipo', ['entrada', 'salida', 'transferencia'])
                ->default('entrada');

            $table->string('observaciones')->nullable();

            $table->timestamps();

            $table->index('serie_id');
            $table->index('movimiento_inventario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_movimientos_serie');
    }
};