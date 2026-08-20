<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('con_asientos_contables', function (Blueprint $table) {
            $table->id();

            // Documento
            $table->string('codigo', 50)->unique();
            $table->string('numero_asiento', 50)->nullable();

            // Fechas
            $table->date('fecha_asiento');
            $table->date('fecha_contable')->nullable();

            // Origen
            $table->string('documento_tipo', 50)->nullable();
            $table->unsignedBigInteger('documento_id')->nullable();
            $table->string('documento_codigo', 50)->nullable();

            // Tipo de asiento
            $table->enum('tipo', [
                'apertura',
                'cierre',
                'diario',
                'compra',
                'venta',
                'ingreso',
                'egreso',
                'ajuste',
                'depreciacion',
                'inventario',
                'conciliacion'
            ])->default('diario');

            // Estado
            $table->enum('estado', ['borrador', 'confirmado', 'anulado'])->default('borrador');

            // Totales
            $table->decimal('total_debe', 18, 6)->default(0);
            $table->decimal('total_haber', 18, 6)->default(0);

            // Información adicional
            $table->text('concepto')->nullable();
            $table->text('observaciones')->nullable();

            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_autorizacion')->nullable();

            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('conf_sucursales')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('fecha_asiento');
            $table->index('documento_tipo');
            $table->index('documento_id');
            $table->index('estado');
            $table->index('tipo');
            $table->index(['documento_tipo', 'documento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('con_asientos_contables');
    }
};
