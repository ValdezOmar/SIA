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
        Schema::create('alm_ubicaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('almacen_id')
                ->constrained('alm_almacenes')
                ->cascadeOnDelete();

            $table->string('codigo');

            $table->string('pasillo')
                ->nullable();

            $table->string('estante')
                ->nullable();

            $table->string('nivel')
                ->nullable();

            $table->string('posicion')
                ->nullable();

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_ubicaciones');
    }
};
