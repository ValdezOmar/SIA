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
        Schema::create('hd_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destinatario_id')->nullable()->constrained('rh_empleados')->nullOnDelete();//Destinatario es a la persona que se le derivo el ticket
            $table->foreignId('equipo_id')->nullable()->constrained('hd_equipos')->nullOnDelete();         
            $table->string('codigo', 100)->unique();
            $table->string('tipo')->nullable(); //'preventivo', 'correctivo'
            $table->string('prioridad')->nullable(); //'baja', 'media', 'alta', 'urgente'
            $table->string('estado')->nullable()->nullable(); //abrierto o cerrado
            $table->string('cli_solicitante')->nullable();//Cliente que solicito
            $table->string('cli_telefono')->nullable();//Telefono de contacto del solicitante
            $table->text('diagnostico')->nullable();//Diagnostico del equipo
            $table->dateTime('fecha_solicitada')->nullable();
            $table->dateTime('fecha_programada')->nullable();
            $table->string('empleado_creacion')->nullable();
            $table->string('adjunto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hd_tickets');
    }
};