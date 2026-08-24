<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ven_facturas', function (Blueprint $table) {
            $table->id();

            // Identificación, alcance y referencias
            $table->string('numero', 50)->unique();
            $table->string('serie', 20)->nullable();
            $table->string('numero_autorizacion', 50)->nullable();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('conf_sucursales')->nullOnDelete();
            $table->string('numero_pedido', 50)->nullable();
            $table->foreignId('pedido_id')->nullable()->constrained('ven_pedidos')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('ven_clientes')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cobrador_id')->nullable()->constrained('users')->nullOnDelete();

            // Fechas, estado y condiciones comerciales
            $table->datetime('fecha_emision');
            $table->datetime('fecha_vencimiento')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->string('estado', 50)->default('borrador');
            $table->string('condicion_pago', 50);
            $table->string('moneda', 3);
            $table->decimal('tasa_cambio', 18, 6)->default(1);

            // Importes, impuestos y pagos acumulados
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6);
            $table->decimal('saldo', 18, 6)->default(0);
            $table->decimal('monto_pagado', 18, 6)->default(0);
            $table->decimal('monto_restante', 18, 6)->default(0);
            $table->string('tipo_impuesto', 20)->default('IVA');
            $table->decimal('tasa_impuesto', 18, 6)->default(13);

            // Información adicional y auditoría
            $table->string('tipo_documento', 50)->default('FACTURA');
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Índices de seguimiento
            $table->index('numero');
            $table->index('cliente_id');
            $table->index('sucursal_id');
            $table->index('estado');
            $table->index('fecha_emision');
            $table->index(['cliente_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ven_facturas');
    }
};
