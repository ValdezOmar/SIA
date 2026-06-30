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
        Schema::create('alm_series', function (Blueprint $table) {
            $table->id();

            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete();

            $table->foreignId('almacen_id')
                ->nullable()
                ->constrained('alm_almacenes')
                ->nullOnDelete();

            $table->string('numero_serie')
                ->unique();

            $table->string('codigo_qr')
                ->nullable();

            $table->string('imei')
                ->nullable();

            $table->string('mac_address')
                ->nullable();

            $table->enum('estado', [
                'disponible',
                'reservado',
                'vendido',
                'baja'
            ])->default('disponible');

            $table->date('fecha_garantia')
                ->nullable();
                
            $table->unsignedBigInteger('cliente_id')
                ->nullable();

            $table->date('fecha_venta')
                ->nullable();

            $table->date('fecha_instalacion')
                ->nullable();

            $table->string('estado_actual')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'articulo_id',
                'numero_serie'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_series');
    }
};
