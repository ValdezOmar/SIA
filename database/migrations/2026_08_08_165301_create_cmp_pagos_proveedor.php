<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmp_pagos_proveedor', function (Blueprint $table) {
            $table->id();
            
            $table->string('codigo', 50)->unique();
            $table->foreignId('factura_id')->constrained('cmp_facturas_compra')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('cmp_proveedores')->cascadeOnDelete();
            
            $table->date('fecha_pago');
            
            $table->enum('tipo_pago', ['efectivo', 'transferencia', 'cheque', 'deposito', 'nota_credito', 'otros'])->default('transferencia');
            $table->decimal('monto', 18, 6);
            $table->string('moneda', 3)->default('BOB');
            $table->decimal('tasa_cambio', 18, 6)->default(1);
            
            $table->string('referencia', 100)->nullable();
            $table->string('banco', 100)->nullable();
            $table->string('numero_cheque', 50)->nullable();
            $table->date('fecha_cheque')->nullable();
            
            $table->enum('estado', ['pendiente', 'confirmado', 'rechazado', 'anulado'])->default('confirmado');
            $table->text('observaciones')->nullable();
            
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('factura_id');
            $table->index('proveedor_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_pagos_proveedor');
    }
};