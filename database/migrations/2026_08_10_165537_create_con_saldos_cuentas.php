<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('con_saldos_cuentas', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('cuenta_id')->constrained('con_plan_cuentas')->cascadeOnDelete();
            
            // Período
            $table->integer('anio');
            $table->integer('mes');
            
            // Saldos
            $table->decimal('saldo_inicial_debe', 18, 6)->default(0);
            $table->decimal('saldo_inicial_haber', 18, 6)->default(0);
            
            $table->decimal('movimiento_debe', 18, 6)->default(0);
            $table->decimal('movimiento_haber', 18, 6)->default(0);
            
            $table->decimal('saldo_final_debe', 18, 6)->default(0);
            $table->decimal('saldo_final_haber', 18, 6)->default(0);
            
            // Naturaleza de la cuenta al momento del saldo
            $table->enum('naturaleza', ['deudora', 'acreedora'])->default('deudora');
            
            // Centro de costo / Proyecto (opcional)
            $table->foreignId('centro_costo_id')->nullable()->constrained('con_centros_costos')->nullOnDelete();
            $table->foreignId('proyecto_id')->nullable()->constrained('con_proyectos')->nullOnDelete();
            
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            
            $table->timestamps();

            $table->unique(['cuenta_id', 'anio', 'mes', 'centro_costo_id', 'proyecto_id'], 'uk_saldo_cuenta_periodo');
            $table->index(['anio', 'mes']);
            $table->index('cuenta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('con_saldos_cuentas');
    }
};