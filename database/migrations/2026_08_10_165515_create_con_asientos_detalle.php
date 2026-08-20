<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('con_asientos_detalle', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asiento_id')->constrained('con_asientos_contables')->cascadeOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('conf_sucursales')->nullOnDelete();
            $table->foreignId('cuenta_id')->constrained('con_plan_cuentas')->cascadeOnDelete();

            $table->unsignedInteger('linea');

            // Descripción
            $table->string('descripcion', 255)->nullable();

            // Valores
            $table->decimal('debe', 18, 6)->default(0);
            $table->decimal('haber', 18, 6)->default(0);

            // Centro de costo / Proyecto
            $table->foreignId('centro_costo_id')->nullable()->constrained('con_centros_costos')->nullOnDelete();
            $table->foreignId('proyecto_id')->nullable()->constrained('con_proyectos')->nullOnDelete();

            // Información adicional
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();

            // Datos adicionales en JSON
            $table->json('datos_adicionales')->nullable();

            $table->timestamps();

            $table->index('asiento_id');
            $table->index('cuenta_id');
            $table->index('centro_costo_id');
            $table->index('proyecto_id');
            $table->unique(['asiento_id', 'linea']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('con_asientos_detalle');
    }
};
