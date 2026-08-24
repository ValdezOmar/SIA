<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmp_facturas_compra', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)->unique();
            $table->string('numero_factura', 50)->nullable();
            $table->string('numero_autorizacion', 50)->nullable();

            $table->foreignId('proveedor_id')->constrained('cmp_proveedores')->cascadeOnDelete();
            $table->foreignId('orden_compra_id')->nullable()->constrained('cmp_ordenes_compra')->nullOnDelete();
            $table->foreignId('recepcion_id')->nullable()->constrained('cmp_recepciones')->nullOnDelete();

            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->json('adjuntos')->nullable()->comment('PDF o imágenes de la factura emitida por el proveedor');

            $table->enum('estado', ['borrador', 'registrada', 'pagada', 'parcial', 'anulada'])->default('borrador');

            $table->string('moneda', 3)->default('BOB');
            $table->decimal('tasa_cambio', 18, 6)->default(1);
            $table->string('condicion_pago', 100)->nullable();

            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6)->default(0);
            $table->decimal('saldo', 18, 6)->default(0);
            $table->decimal('monto_pagado', 18, 6)->default(0);
            $table->boolean('pago_pendiente')->default(false)->comment('Indica que el pago parcial debe completarse');

            $table->text('observaciones')->nullable();

            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('proveedor_id');
            $table->index('estado');
            $table->index('orden_compra_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_facturas_compra');
    }
};
