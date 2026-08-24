<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ven_pedidos_detalle', function (Blueprint $table) {
            $table->id();

            // Relación y orden de la línea
            $table->foreignId('pedido_id')->constrained('ven_pedidos')->cascadeOnDelete();
            $table->unsignedInteger('linea');
            $table->foreignId('articulo_id')->constrained('alm_articulos')->cascadeOnDelete();
            $table->foreignId('lista_precio')->nullable()->constrained('alm_listas_precios')->nullOnDelete();

            // Instantánea del artículo y cantidad
            $table->string('codigo_articulo', 50);
            $table->string('descripcion_articulo', 255);
            $table->string('unidad_medida', 100)->nullable();
            $table->decimal('cantidad', 18, 6);
            $table->integer('tiempo_entrega_dias')->nullable();

            // Precios e impuestos
            $table->decimal('precio_unitario', 18, 6)->default(0);
            $table->decimal('precio_original', 18, 6)->default(0);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('descuento_porcentaje', 18, 6)->default(0);
            $table->decimal('subtotal', 18, 6);
            $table->boolean('aplicar_iva')->default(false);
            $table->string('tipo_impuesto', 20)->default('IVA');
            $table->decimal('tasa_impuesto', 18, 6)->default(13);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6)->default(0);

            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pedido_id');
            $table->index('articulo_id');
            $table->unique(['pedido_id', 'linea']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ven_pedidos_detalle');
    }
};
