<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ven_pedidos', function (Blueprint $table) {
            $table->id();

            // Identificación, alcance y referencias
            $table->string('codigo', 50)->unique();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('conf_sucursales')->nullOnDelete();
            $table->foreignId('cotizacion_id')->nullable()->constrained('ven_cotizaciones')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('ven_clientes')->nullOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();

            // Fechas, estado y condiciones comerciales
            $table->date('fecha_pedido');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->date('fecha_entrega_real')->nullable();
            $table->enum('estado', ['reservado', 'pendiente', 'parcial', 'despachado', 'entregado', 'cancelado'])->default('reservado');
            $table->enum('prioridad', ['baja', 'normal', 'alta', 'urgente'])->default('normal');
            $table->string('condicion_pago', 100)->nullable();
            $table->char('moneda', 3)->default('BOB');
            $table->decimal('tasa_cambio', 18, 6)->default(1);

            // Importes y entrega
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6)->default(0);
            $table->text('direccion_envio')->nullable();
            $table->string('metodo_envio', 100)->nullable();
            $table->decimal('costo_envio', 18, 6)->default(0);

            // Observaciones y auditoría
            $table->text('observaciones')->nullable();
            $table->text('instrucciones_especiales')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Índices de seguimiento
            $table->index('codigo');
            $table->index('cliente_id');
            $table->index('estado');
            $table->index('fecha_pedido');
            $table->index('empresa_id');
            $table->index('sucursal_id');
            $table->index('vendedor_id');
            $table->index('creado_por');
            $table->index(['empresa_id', 'estado']);
            $table->index(['cliente_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ven_pedidos');
    }
};
