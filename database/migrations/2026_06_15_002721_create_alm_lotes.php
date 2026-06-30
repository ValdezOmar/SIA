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
        Schema::create('alm_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete();

            $table->string('numero_lote');

            $table->date('fecha_fabricacion')
                ->nullable();

            $table->date('fecha_vencimiento')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'articulo_id',
                'numero_lote'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_lotes');
    }
};
