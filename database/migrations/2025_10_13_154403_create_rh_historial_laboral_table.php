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
        Schema::create('rh_historial_laboral', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')
                ->constrained('rh_empleados')
                ->onDelete('cascade');
            $table->foreignId('empresa_id')
                ->nullable()
                ->constrained('conf_empresas')
                ->nullOnDelete();
            $table->foreignId('cargo_id')
                ->nullable()
                ->constrained('conf_cargos') 
                ->nullOnDelete();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->date('fecha_baja')->nullable();            
            $table->decimal('salario', 12, 2)->nullable();
            $table->string('tipo_contrato')->nullable();     
            $table->string('seguro_medico')->nullable();
            $table->string('sucursal')->nullable();
            $table->string('correo_corporativo')->nullable();
            $table->string('numero_corporativo')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('documento')->nullable();
            $table->boolean('activo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rh_historial_laboral');
    }
};