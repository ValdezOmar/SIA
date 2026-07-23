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
        Schema::create('ven_facturas_detalle', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('factura_id')->constrained('ven_facturas')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('alm_articulos')->cascadeOnDelete();
            
            // Datos del artículo
            $table->string('codigo_articulo', 50);
            $table->string('descripcion_articulo', 255);
            $table->string('unidad_medida', 20)->default('UND');
            
            // Cantidades
            $table->decimal('cantidad', 18, 6);
            
            // Precios
            $table->decimal('precio_unitario', 18, 6);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('descuento_porcentaje', 18, 6)->default(0);
            $table->decimal('subtotal', 18, 6);
            
            // Impuestos
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6);
            
            // Información adicional
            $table->text('observaciones')->nullable();
            
            // Series y lotes (si aplica)
            $table->json('series')->nullable();
            $table->json('lotes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('factura_id');
            $table->index('articulo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ven_facturas_detalle');
    }
};
