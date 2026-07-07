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
        Schema::create('ven_cotizaciones', function (Blueprint $table) {
            $table->id();

            // Número de documento
            $table->string('codigo', 50)->unique();
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('conf_sucursales')
                ->nullOnDelete();

            // Fechas
            $table->date('fecha_emision');
            $table->date('fecha_validez')->nullable();
            $table->date('fecha_entrega_estimada')->nullable();

            // Cliente
            $table->foreignId('cliente_id')
            ->nullable()
            ->constrained('ven_clientes')
            ->nullOnDelete();

            // Condiciones
            $table->enum('estado', [
                'borrador',
                'enviada',
                'aprobada',
                'rechazada',
                'convertida',
                'expirada'
            ])->default('borrador');

            $table->string('condicion_pago', 100)->nullable();

            $table->decimal('tasa_cambio', 18, 6)->default(1)->nullable();
            $table->char('moneda', 3)->default('BOB');

            // Totales
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('descuento_porcentaje', 18, 6)->default(0);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6)->default(0);

            // Impuestos
            $table->string('tipo_impuesto', 20)->default('IVA');
            $table->decimal('tasa_impuesto', 18, 6)->default(13);

            // Información adicional
            $table->text('observaciones')->nullable();
            $table->text('condiciones_especiales')->nullable();

            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('codigo');
            $table->index('cliente_id');
            $table->index('estado');
            $table->index('fecha_emision');
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
        Schema::dropIfExists('ven_cotizaciones');
    }
};
