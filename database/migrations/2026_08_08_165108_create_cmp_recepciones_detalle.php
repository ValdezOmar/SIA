<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmp_recepciones_detalle', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('recepcion_id')->constrained('cmp_recepciones')->cascadeOnDelete();
            $table->foreignId('orden_detalle_id')->constrained('cmp_ordenes_compra_detalle')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('alm_articulos')->cascadeOnDelete();
            $table->unsignedInteger('linea');
            
            $table->string('codigo_articulo', 50);
            $table->string('descripcion_articulo', 255);
            $table->string('unidad_medida', 20)->nullable();
            
            $table->decimal('cantidad', 18, 6);
            $table->decimal('cantidad_aceptada', 18, 6)->default(0);
            $table->decimal('cantidad_rechazada', 18, 6)->default(0);
            
            $table->decimal('costo_unitario', 18, 6)->default(0);
            $table->decimal('costo_total', 18, 6)->default(0);
            
            $table->text('motivo_rechazo')->nullable();
            $table->text('observaciones')->nullable();

            // Identificación de unidades y distribución de cantidades por lote.
            $table->json('series')->nullable();
            $table->json('lotes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('recepcion_id');
            $table->index('orden_detalle_id');
            $table->index('articulo_id');
            $table->unique(['recepcion_id', 'linea']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_recepciones_detalle');
    }
};