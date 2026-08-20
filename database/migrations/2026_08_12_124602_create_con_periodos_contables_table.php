<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('con_periodos_contables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('conf_empresas')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('conf_sucursales')->nullOnDelete();
            
            $table->integer('anio');
            $table->integer('mes');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['abierto', 'cerrado', 'bloqueado'])->default('abierto');
            $table->date('fecha_cierre')->nullable();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            $table->unique(['empresa_id', 'sucursal_id', 'anio', 'mes']);
            $table->index(['empresa_id', 'estado']);
        });
    }
};