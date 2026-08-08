<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmp_solicitudes_compra', function (Blueprint $table) {
            $table->id();
            
            // Documento
            $table->string('codigo', 50)->unique();
            $table->foreignId('sucursal_id')->nullable()->constrained('conf_sucursales')->nullOnDelete();
            
            // Fechas
            $table->date('fecha_solicitud');
            $table->date('fecha_requerida')->nullable();
            
            // Origen
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            
            // Estado
            $table->enum('estado', [
                'borrador',
                'pendiente',
                'aprobada',
                'rechazada',
                'en_cotizacion',
                'convertida'
            ])->default('borrador');
            
            $table->enum('prioridad', ['baja', 'normal', 'alta', 'urgente'])->default('normal');
            
            // Totales
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('impuesto', 18, 6)->default(0);
            $table->decimal('total', 18, 6)->default(0);
            
            // Información adicional
            $table->text('justificacion')->nullable();
            $table->text('observaciones')->nullable();
            
            // Auditoría
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('estado');
            $table->index('fecha_solicitud');
            $table->index('solicitado_por');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_solicitudes_compra');
    }
};