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
        Schema::create('empleados', function (Blueprint $table) {
            //Información Básica del Empleado
            $table->id(); 
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('ci')->unique(); // Cédula de identidad            
            $table->date('fecha_nacimiento')->nullable();
            $table->string('direccion')->nullable();
            $table->string('ubicacion_gps')->nullable();
            $table->string('genero')->nullable(); // hombre, mujer, otro
            $table->string('nacionalidad')->default('Boliviana');
            //Datos Personales Adicionales
            $table->enum('estado_civil', ['soltero', 'casado', 'viudo', 'divorciado'])->nullable();
            $table->integer('cantidad_hijos')->nullable();
            $table->string('telefono_personal')->nullable();
            $table->string('correo_personal')->nullable();
            $table->string('persona_contacto')->nullable(); // persona en caso de emergencia
            $table->string('numero_contacto')->nullable(); // número de emergencia
            $table->string('nua_cua')->nullable();            
            //Datos Laborales
            $table->boolean('activo')->default(true); // si sigue en la empresa
            $table->string('foto')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_desvinculacion')->nullable();
            $table->string('estado_contrato')->nullable();
            $table->string('afp')->nullable();
            $table->string('caja_salud')->nullable();            
            $table->string('correo_corporativo')->nullable();
            $table->string('numero_corporativo')->nullable();
            $table->string('cargo')->nullable(); // cargo actual
            $table->enum('sucursal', ['La Paz', 'Santa Cruz', 'Cochabamba', 'Oruro', 'Potosí', 'Tarija', 'Sucre', 'Beni', 'Pando'])->nullable(); // departamento donde trabaja
            $table->enum('empresa', ['Novanexa', 'Ireilab', 'Requilab'])->nullable();    
            $table->float('salario')->nullable();                   
            //Control
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
