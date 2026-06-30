<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_almacenes', function (Blueprint $table) {
            $table->id();
            
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('conf_sucursales')
                ->nullOnDelete();

            $table->foreignId('empresa_id')
                ->nullable()
                ->constrained('conf_empresas')
                ->nullOnDelete();

            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índices
            $table->index('activo');
            $table->index('sucursal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_almacenes');
    }
};