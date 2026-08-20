<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('con_tipos_cambio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('conf_empresas')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('conf_sucursales')->nullOnDelete();
            
            $table->string('moneda_origen', 3);
            $table->string('moneda_destino', 3);
            $table->decimal('tasa_compra', 18, 6);
            $table->decimal('tasa_venta', 18, 6);
            $table->date('fecha');
            $table->boolean('activo')->default(true);
            
            $table->timestamps();
        });
    }
};