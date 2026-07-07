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
        Schema::create('ven_pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('factura_id')->constrained('ven_facturas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('ven_clientes')->cascadeOnDelete();

            // Datos del pago
            $table->string('numero', 50)->unique();
            $table->date('fecha_pago');

            $table->enum('tipo_pago', [
                'efectivo',
                'transferencia',
                'cheque',
                'tarjeta',
                'deposito',
                'nota_credito',
                'otros'
            ])->default('efectivo');

            $table->decimal('monto', 18, 6);
            $table->string('moneda', 3)->default('BOB');
            $table->decimal('tasa_cambio', 18, 6)->default(1);

            // Referencia
            $table->string('referencia', 100)->nullable();
            $table->string('banco', 100)->nullable();
            $table->string('numero_cheque', 50)->nullable();
            $table->date('fecha_cheque')->nullable();

            // Estado
            $table->enum('estado', [
                'pendiente',
                'confirmado',
                'rechazado',
                'anulado'
            ])->default('pendiente');

            // Información adicional
            $table->text('observaciones')->nullable();

            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('factura_id');
            $table->index('cliente_id');
            $table->index('estado');
            $table->index('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ven_pagos');
    }
};
