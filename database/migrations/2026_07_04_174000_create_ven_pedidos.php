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
        Schema::create('ven_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('conf_sucursales')
                ->nullOnDelete();

            // Número de documento
            $table->string('codigo', 50)->unique();

            // Referencias
            $table->foreignId('cotizacion_id')->nullable()->constrained('ven_cotizaciones')->nullOnDelete();

            // Fechas
            $table->date('fecha_pedido');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->date('fecha_entrega_real')->nullable();

            // Cliente
            $table->foreignId('cliente_id')
            ->nullable()
            ->constrained('ven_clientes')
            ->nullOnDelete();

            // Estado
            $table->enum('estado', [
                'reservado',
                'pendiente',
                'parcial',
                'despachado',
                'entregado',
                'cancelado'
            ])->default('reservado');

            $table->enum('prioridad', [
                'baja',
                'normal',
                'alta',
                'urgente'
            ])->default('normal');

            // Condiciones
            $table->string('condicion_pago', 100)->nullable();

            $table->char('moneda', 3)->default('BOB');
            $table->decimal('tasa_cambio', 18, 6)->default(1);

            // Totales
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6)->default(0);

            // Envío
            $table->text('direccion_envio')->nullable();
            $table->string('metodo_envio', 100)->nullable();
            $table->decimal('costo_envio', 18, 6)->default(0);

            // Información adicional
            $table->text('observaciones')->nullable();
            $table->text('instrucciones_especiales')->nullable();

            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('codigo');
            $table->index('cliente_id');
            $table->index('estado');
            $table->index('fecha_pedido');
            $table->index('empresa_id');
            $table->index('sucursal_id');
            $table->index('vendedor_id');
            $table->index('creado_por');
            $table->index([
                'empresa_id',
                'estado'
            ]);

            $table->index([
                'cliente_id',
                'estado'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ven_pedidos');
    }
};
