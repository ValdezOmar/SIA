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
        Schema::create('rh_asistencias', function (Blueprint $table) {
            $table->id();
            $table->text('id_equipo')->nullable(); // ID único del equipo
            $table->string('user_id'); // ID de usuario registrado en el biométrico
            $table->date('fecha');     // Cambiado de timestamp a date
            $table->time('hora');      // Cambiado de timestamp a time
            $table->boolean('registro_remoto')->nullable(); // Puntero para saber si registraron en sitio
            $table->string('localizacion')->nullable(); // Geolocallzacion para saber donde realizar el registro remoto
            $table->string('justificacion')->nullable(); // Justificacion de porque se esta realizando el marcado remoto
            $table->boolean('visible')->default(true);//muestra la marcacion en sistema
            $table->index('user_id', 'asistencias_user_id_index');
            $table->index('fecha', 'asistencias_fecha_index');
            $table->index(['user_id', 'fecha'], 'asistencias_user_fecha_index');
            
            //$table->foreign('user_id')->references('ci')->on('empleados');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rh_asistencias');
    }
};