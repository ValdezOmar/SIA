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
        Schema::create('cmp_articulos_proveedores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete();

            $table->foreignId('proveedor_id')
                ->constrained('cmp_proveedores')
                ->cascadeOnDelete();

            $table->string('codigo_proveedor')
                ->nullable();

            $table->decimal('costo_compra', 18, 6)
                ->default(0);

            $table->boolean('es_principal')
                ->default(false);

            $table->timestamps();

            $table->unique([
                'articulo_id',
                'proveedor_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmp_articulos_proveedores');
    }
};
