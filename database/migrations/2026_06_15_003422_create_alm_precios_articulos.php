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
        Schema::create('alm_precios_articulos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete();

            $table->foreignId('lista_precio_id')
                ->constrained('alm_listas_precios')
                ->cascadeOnDelete();

            $table->decimal('precio', 18, 6);

            $table->timestamps();

            $table->unique([
                'articulo_id',
                'lista_precio_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_precios_articulos');
    }
};
