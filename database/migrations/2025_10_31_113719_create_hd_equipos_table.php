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
        Schema::create('hd_equipos', function (Blueprint $table) {
            $table->id();

            //Datos basicos
            $table->string('codigo', 100)->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('com_clientes')->nullOnDelete();
            $table->text('descripcion')->nullable();//Es la descipcion detallada del equipo
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('num_serie')->nullable();
            //Datos Comerciales           
            $table->text('observaciones')->nullable();//Son todas las observaciones durante la entrega
            $table->string('tipo_venta')->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->date('fecha_devolucion')->nullable();
            $table->date('garantia_desde')->nullable();
            $table->date('garantia_hasta')->nullable();
            $table->string('foto_equipo')->nullable();
            $table->string('doc_adjunto')->nullable();

            //Datos adicionales
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('conf_sucursales')->nullOnDelete();
            $table->foreignId('tecnico_asignado')->nullable()->constrained('rh_empleados')->nullOnDelete();
            $table->integer('tel_soporte')->nullable();
            $table->json('freq_mantenimiento')->nullable();
            $table->text('direccion')->nullable();
            $table->json('ubicacion_gps')->nullable();

            //Conttroles
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hd_equipos');
    }
};