<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_existencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete();

            $table->foreignId('almacen_id')
                ->constrained('alm_almacenes')
                ->cascadeOnDelete();

            $table->decimal('cantidad_disponible', 18, 6)
                ->default(0)
                ->comment('Cantidad disponible en el almacén');

            $table->decimal('cantidad_comprometida', 18, 6)
                ->default(0)
                ->comment('Cantidad comprometida para pedidos');

            $table->decimal('cantidad_pedida', 18, 6)
                ->default(0)
                ->comment('Cantidad pedida a proveedores');

            $table->decimal('cantidad_minima', 18, 6)
                ->default(0)
                ->comment('Cantidad mínima de stock');

            $table->decimal('cantidad_maxima', 18, 6)
                ->nullable()
                ->comment('Cantidad máxima de stock');

            $table->decimal('costo_promedio', 18, 6)
                ->default(0)
                ->after('cantidad_maxima')
                ->comment('Costo promedio del inventario');

            $table->decimal('costo_acumulado', 18, 6)
                ->default(0)
                ->after('costo_promedio')
                ->comment('Costo acumulado total del inventario');

            $table->decimal('ultimo_costo', 18, 6)
                ->default(0)
                ->after('costo_acumulado')
                ->comment('Último costo registrado');

            $table->timestamp('ultima_entrada')
                ->nullable()
                ->after('ultimo_costo')
                ->comment('Fecha de la última entrada');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['articulo_id', 'almacen_id']);
            $table->index(['articulo_id', 'almacen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_existencias');
    }
};
