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

            // Relaciones
            $table->foreignId('factura_id')->constrained('ven_facturas')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('alm_articulos')->cascadeOnDelete();
            // Capa de costo específica seleccionada para valorar la salida.
            $table->unsignedBigInteger('capa_costo_id')->nullable();
            $table->foreignId('lista_precio')->nullable()->constrained('alm_listas_precios')->nullOnDelete();

            // Datos del artículo (copiados para preservar históricos)
            $table->string('codigo_articulo', 50);
            $table->string('descripcion_articulo', 255);
            $table->string('unidad_medida', 20)->default('UND');

            // Cantidades y precios
            $table->decimal('cantidad', 18, 6)->default(1);
            $table->decimal('precio_unitario', 18, 6);
            $table->decimal('precio_original', 18, 6)->default(0);

            // Descuentos
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('descuento_porcentaje', 18, 6)->default(0);

            // Totales
            $table->decimal('subtotal', 18, 6);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6);

            // Impuestos
            $table->string('tipo_impuesto', 20)->default('IVA');
            $table->decimal('tasa_impuesto', 18, 6)->default(13);
            $table->boolean('aplicar_iva')->default(false);

            // Observaciones
            $table->text('observaciones')->nullable();

            // Series y lotes (si aplica)
            $table->json('series')->nullable();
            $table->json('lotes')->nullable();

            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('factura_id');
            $table->index('articulo_id');
            $table->index(['factura_id', 'articulo_id']);
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