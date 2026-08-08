<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmp_ordenes_compra', function (Blueprint $table) {
            $table->id();
            
            // Documento
            $table->string('codigo', 50)->unique();
            $table->foreignId('sucursal_id')->nullable()->constrained('conf_sucursales')->nullOnDelete();
            
            // Referencias
            $table->foreignId('proveedor_id')->constrained('cmp_proveedores')->cascadeOnDelete();
            $table->foreignId('solicitud_id')->nullable()->constrained('cmp_solicitudes_compra')->nullOnDelete();
            $table->foreignId('cotizacion_proveedor_id')->nullable()->constrained('cmp_cotizaciones_proveedor')->nullOnDelete();
            
            // Fechas
            $table->date('fecha_orden');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->date('fecha_entrega_real')->nullable();
            
            // Estado
            $table->enum('estado', [
                'borrador',
                'enviada',
                'confirmada',
                'parcial',
                'recibida',
                'completada',
                'cancelada'
            ])->default('borrador');
            
            // Condiciones
            $table->string('moneda', 3)->default('BOB');
            $table->decimal('tasa_cambio', 18, 6)->default(1);
            $table->string('condicion_pago', 100)->nullable();
            $table->string('metodo_envio', 100)->nullable();
            
            // Totales
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6)->default(0);
            
            // Información adicional
            $table->text('direccion_entrega')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('terminos_condiciones')->nullable();
            
            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('proveedor_id');
            $table->index('estado');
            $table->index('fecha_orden');
            $table->index('solicitud_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_ordenes_compra');
    }
};