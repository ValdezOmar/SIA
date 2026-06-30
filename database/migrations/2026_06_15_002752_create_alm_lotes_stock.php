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
        Schema::create('alm_lotes_stock', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lote_id')
                ->constrained('alm_lotes')
                ->cascadeOnDelete();

            $table->foreignId('almacen_id')
                ->constrained('alm_almacenes')
                ->cascadeOnDelete();

            $table->decimal('cantidad', 18, 6)
                ->default(0);

            $table->timestamps();

            $table->unique([
                'lote_id',
                'almacen_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_lotes_stock');
    }
};
