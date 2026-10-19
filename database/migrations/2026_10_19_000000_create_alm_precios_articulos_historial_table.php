<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_precios_articulos_historial', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('precio_articulo_id')->nullable()->index();
            $table->unsignedBigInteger('articulo_id')->index();
            $table->string('articulo_codigo')->nullable();
            $table->string('articulo_nombre')->nullable();
            $table->string('accion', 20)->index();
            $table->unsignedBigInteger('lista_precio_anterior_id')->nullable();
            $table->string('lista_precio_anterior_codigo')->nullable();
            $table->string('lista_precio_anterior_nombre')->nullable();
            $table->string('lista_precio_anterior_moneda', 10)->nullable();
            $table->unsignedBigInteger('lista_precio_nuevo_id')->nullable();
            $table->string('lista_precio_nuevo_codigo')->nullable();
            $table->string('lista_precio_nuevo_nombre')->nullable();
            $table->string('lista_precio_nuevo_moneda', 10)->nullable();
            $table->decimal('precio_anterior', 18, 6)->nullable();
            $table->decimal('precio_nuevo', 18, 6)->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable()->index();
            $table->string('usuario_nombre')->nullable();
            $table->dateTime('registrado_en')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_precios_articulos_historial');
    }
};
