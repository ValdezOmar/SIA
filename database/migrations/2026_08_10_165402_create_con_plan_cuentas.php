<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('con_plan_cuentas', function (Blueprint $table) {
            $table->id();
            
            // Datos de la cuenta
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 255);
            $table->string('nombre_completo', 255)->nullable();
            $table->text('descripcion')->nullable();
            
            // Jerarquía
            $table->foreignId('cuenta_padre_id')->nullable()->constrained('con_plan_cuentas')->nullOnDelete();
            $table->integer('nivel')->default(1);
            $table->string('trayectoria', 255)->nullable(); // Ruta jerárquica ej: 1.1.1
            
            // Clasificación
            $table->enum('tipo_cuenta', [
                'activo',
                'pasivo',
                'patrimonio',
                'ingreso',
                'gasto',
                'costo'
            ])->default('activo');
            
            // Naturaleza
            $table->enum('naturaleza', ['deudora', 'acreedora'])->default('deudora');
            
            // Tipo de cuenta para detalle
            $table->enum('tipo_detalle', [
                'general',
                'auxiliar',
                'analitica',
                'control',
                'ajuste'
            ])->default('general');
            
            // Configuración
            $table->boolean('es_control')->default(false);
            $table->boolean('es_analitica')->default(false);
            $table->boolean('permite_movimiento')->default(true);
            $table->boolean('requiere_centro_costo')->default(false);
            $table->boolean('requiere_proyecto')->default(false);
            
            // Estado
            $table->boolean('activo')->default(true);
            
            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('tipo_cuenta');
            $table->index('trayectoria');
            $table->index('nivel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('con_plan_cuentas');
    }
};