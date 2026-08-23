<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_transferencias_almacenes_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transferencia_id')->constrained('alm_transferencias_almacenes')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('alm_articulos')->restrictOnDelete();
            $table->decimal('cantidad', 18, 6);
            $table->json('series')->nullable();
            $table->json('lotes')->nullable();
            $table->decimal('costo_unitario_salida', 18, 6)->nullable();
            $table->foreignId('kardex_salida_id')->nullable()->constrained('alm_kardex')->nullOnDelete();
            $table->foreignId('kardex_entrada_id')->nullable()->constrained('alm_kardex')->nullOnDelete();
            $table->timestamps();
            $table->unique(['transferencia_id', 'articulo_id'], 'alm_tra_det_transferencia_articulo_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_transferencias_almacenes_detalle');
    }
};
