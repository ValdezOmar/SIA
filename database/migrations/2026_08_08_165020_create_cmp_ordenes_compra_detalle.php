<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmp_ordenes_compra_detalle', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('orden_id')->constrained('cmp_ordenes_compra')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('alm_articulos')->cascadeOnDelete();
            $table->unsignedInteger('linea');
            
            // Datos del artículo
            $table->string('codigo_articulo', 50);
            $table->string('descripcion_articulo', 255);
            $table->string('unidad_medida', 20)->nullable();
            
            // Cantidades
            $table->decimal('cantidad', 18, 6);
            $table->decimal('cantidad_recibida', 18, 6)->default(0);
            $table->decimal('cantidad_facturada', 18, 6)->default(0);
            
            // Precios
            $table->decimal('precio_unitario', 18, 6);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('subtotal', 18, 6);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6);
            
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('orden_id');
            $table->index('articulo_id');
            $table->unique(['orden_id', 'linea']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_ordenes_compra_detalle');
    }
};