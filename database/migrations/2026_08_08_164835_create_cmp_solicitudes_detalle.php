<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmp_solicitudes_detalle', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('solicitud_id')->constrained('cmp_solicitudes_compra')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('alm_articulos')->cascadeOnDelete();
            $table->unsignedInteger('linea');
            
            // Datos del artículo
            $table->string('codigo_articulo', 50);
            $table->string('descripcion_articulo', 255);
            $table->string('unidad_medida', 20)->nullable();
            
            // Cantidades
            $table->decimal('cantidad', 18, 6);
            $table->decimal('cantidad_atendida', 18, 6)->default(0);
            
            // Precios estimados
            $table->decimal('precio_estimado', 18, 6)->default(0);
            $table->decimal('subtotal', 18, 6)->default(0);
            
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('solicitud_id');
            $table->index('articulo_id');
            $table->unique(['solicitud_id', 'linea']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_solicitudes_detalle');
    }
};