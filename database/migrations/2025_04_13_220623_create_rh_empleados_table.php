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
        Schema::create('rh_empleados', function (Blueprint $table) {
            // Información Básica del Empleado
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('ci')->unique(); // Cédula de identidad
            $table->date('fecha_nacimiento')->nullable();
            $table->string('direccion')->nullable();
            $table->json('ubicacion_gps')->nullable();
            $table->string('genero')->nullable(); // hombre, mujer, otro
            $table->string('nacionalidad')->default('Boliviana');
            // Datos Personales Adicionales
            $table->string('estado_civil')->nullable();
            $table->integer('cantidad_hijos')->nullable();
            $table->string('telefono_personal')->nullable();
            $table->string('correo_personal')->nullable();
            $table->string('persona_contacto')->nullable(); // persona en caso de emergencia
            $table->string('numero_contacto')->nullable(); // número de emergencia
            $table->string('persona_parentesco')->nullable(); // persona en caso de emergencia
            $table->string('nua_cua')->nullable();
            // Estado y archivos propios del empleado. La información laboral vive en rh_historial_laboral.
            $table->boolean('activo')->default(true);
            $table->string('foto')->nullable();
            $table->string('afp')->nullable();
            // Control
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rh_empleados');
    }
};
