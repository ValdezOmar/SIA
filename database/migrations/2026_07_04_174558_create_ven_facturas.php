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
        Schema::create('ven_facturas', function (Blueprint $table) {
            $table->id();
            
            // Número de documento
            $table->string('numero', 50)->unique();
            $table->string('serie', 20)->nullable();
            $table->string('numero_autorizacion', 50)->nullable();
            
            // Referencias
            $table->string('numero_pedido', 50)->nullable();
            $table->foreignId('pedido_id')->nullable()->constrained('ven_pedidos')->nullOnDelete();
            
            // Fechas
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->date('fecha_pago')->nullable();
            
            // Cliente
            $table->foreignId('cliente_id')->constrained('ven_clientes')->cascadeOnDelete();
            
            // Estado
            $table->enum('estado', [
                'borrador',
                'emitida',
                'pagada',
                'parcial',
                'vencida',
                'anulada'
            ])->default('borrador');
            
            // Condiciones
            $table->string('condicion_pago', 50)->default('contado');
            
            $table->string('moneda', 3)->default('BOB');
            $table->decimal('tasa_cambio', 18, 6)->default(1);
            
            // Totales
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6);
            $table->decimal('saldo', 18, 6)->default(0);
            
            // Pago
            $table->decimal('monto_pagado', 18, 6)->default(0);
            $table->decimal('monto_restante', 18, 6)->default(0);
            
            // Impuestos
            $table->string('tipo_impuesto', 20)->default('IVA');
            $table->decimal('tasa_impuesto', 18, 6)->default(13);
            
            // Información adicional
            $table->text('observaciones')->nullable();
            $table->string('tipo_documento', 50)->default('FACTURA');
            
            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cobrador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('numero');
            $table->index('cliente_id');
            $table->index('estado');
            $table->index('fecha_emision');
            $table->index(['cliente_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ven_facturas');
    }
};
