<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmp_cotizaciones_proveedor', function (Blueprint $table) {
            $table->id();
            
            $table->string('codigo', 50)->unique();
            $table->foreignId('solicitud_id')->nullable()->constrained('cmp_solicitudes_compra')->nullOnDelete();
            $table->foreignId('proveedor_id')->constrained('cmp_proveedores')->cascadeOnDelete();
            
            $table->date('fecha_cotizacion');
            $table->date('fecha_validez')->nullable();
            
            $table->string('moneda', 3)->default('BOB');
            $table->decimal('tasa_cambio', 18, 6)->default(1);
            
            // Totales
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('descuento', 18, 6)->default(0);
            $table->decimal('total', 18, 6)->default(0);
            
            // Condiciones
            $table->string('condicion_pago', 100)->nullable();
            $table->integer('tiempo_entrega_dias')->nullable();
            $table->string('lugar_entrega', 255)->nullable();
            
            $table->enum('estado', ['recibida', 'evaluada', 'aceptada', 'rechazada'])->default('recibida');
            
            $table->text('observaciones')->nullable();
            
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('proveedor_id');
            $table->index('estado');
            $table->index('solicitud_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_cotizaciones_proveedor');
    }
};